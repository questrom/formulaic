<?php

use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;
use voku\helper\UTF8;

# An interface implemented by all elements that can go in the "outputs"
# section of a configuration file.
interface Output {

	# $data - the data to be stored
	# $page - the current form
	function run($data, $page);

}

# An interface implemented by those outputs which can also be used to retrive
# data for tables and graphs. Currently, this only includes MongoDB.
interface Storage extends Output {
	function count();
	function getById($id);
	function getStats($unwindArrays, $name);
	function getTable($page, $sortBy, $start, $count);
}

# MongoDB storage
class MongoOutput implements Configurable, Storage {


	# Construct an object from an element in a configuration file
	function __construct($args) {

		$config = Config::get()['mongo'];

		$this->server = $config['server'];
		$this->database = $config['database'];
		$this->collection = $args['collection'];
		$this->client = null;
	}

	# Get the associated MongoDB client
	private function getClient() {
		if($this->client === null) {
			$this->client = (new MongoClient($this->server, [
					'connect' => false
				]))
				->selectDB($this->database)
				->selectCollection($this->collection);
		}
		return $this->client;
	}

	# Count total number of submissions
	function count() {
		return $this->getClient()->count();
	}

	# Store form data in MongoDB
	function run($data, $page) {
		$collection = $this->getClient();
		$collection->insert(buildMongoOutput($data));
		return $data;
	}

	# Get a single form submission by ID
	function getById($id) {
		$client = $this->getClient();

		$data = $client->findOne([
			'_id' => new MongoId($id)
		]);

		$data = fixMongoDates($data);

		return $data;
	}

	# Get statistics to be put in a graph, from the name of a form field.
	# $unwindArrays is needed if the data being processed consists of arrays.
	function getStats($unwindArrays, $name) {
		$client = $this->getClient();
		if($unwindArrays) {
			// to handle array case
			$results = $client->aggregate([
				[
					'$unwind' => '$' . $name
				],
				[
					'$group'  => [
						'_id' => '$' . $name,
						'count' => [ '$sum' => 1 ]
					]
				],
				[
					'$sort' => [
						'count' => -1
					]
				]
			]);
		} else {
			$results = $client->aggregate(
				[
					'$group'  => [
						'_id' => '$' . $name,
						'count' => [ '$sum' => 1 ]
					]
				],
				[
					'$sort' => [
						'count' => -1
					]
				]
			);
		}

		return $results['result'];
	}

	# Get all the data necessary for creating a table.
	function getTable($page, $sortBy, $start, $count) {

		$client = $this->getClient();
		$cursor = $client->find()->sort($sortBy);

		if($count !== null) {
			$cursor->skip($start);
			$cursor->limit($count);
		}

		return fixMongoDates(array_values(iterator_to_array($cursor)));
	}
}

# Amazon S3 storage
class S3Output implements Output, Configurable {


	# Create from an element in the configuration file and from options in the config.toml.
	# Key and secret are stored in config.toml so people with access to the configuration
	# files can't necessarily see this (sensitive) information.
	function __construct($args) {
		$this->secret =Config::get();
		$this->s3 = new S3($this->secret['s3']['key'], $this->secret['s3']['secret']);
		$this->bucket = $args['bucket'];
	}

	# Store files in S3, replacing the FileInfo objects in the data with
	# information about the objects stored in S3.
	function run($data, $page) {
		return array_map(function($x) use ($page) {
			if(is_array($x)) {
				return $this->run($x, $page);
			} elseif ($x instanceof FileInfo) {
				$this->s3->putObject(
					S3::inputFile($x->file['tmp_name'], false),
					$this->bucket,
					$x->filename,
					$x->permissions,
					[],
					[ 'Content-Type' => $x->mime ]
				);

				# Based on code from amazon-s3-php-class
				$url = 'https://s3.amazonaws.com/' . $this->bucket . '/' . rawurlencode($x->filename);

				return [
					'url' => $url,
					'bucket' => $this->bucket,
					'name' => $x->filename,
					'originalName' => $x->file['name'],
					'mime' => $x->mime
				];
			} else {
				return $x;
			}
		}, $data);
	}
}

# Send form submissions via email
class EmailOutput implements Output, Configurable {

	function __construct($args) {
		$this->to = $args['to'];
		$this->from = $args['from'];
		$this->subject = $args['subject'];
	}
	function run($data, $page) {

		# Create an HTML email
		$view = new EmailView($page);
		$view->data = $data;

		$html = '<!DOCTYPE html>' . Stringifier::stringify($view->makeEmailView());

		# ... and send it using nette/mail
		$mail = new Message();
		$mail
			->setFrom($this->from)
		    ->addTo($this->to)
		    ->setSubject($this->subject)
		    ->setHTMLBody($html);

		$mailer = new SmtpMailer(Config::get()['smtp']);

		$mailer->send($mail);

		return $data;
	}
}

# Renderable for confirmation emails
class ConfirmationEmail implements Renderable {
	function __construct($output, $timestamp) { $this->output = $output; $this->timestamp = $timestamp; }
	function render() {
		return
		h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->c($this->output->subject)->end
			->end
			->body
				# styles based on semantic UI
				->div->style('border: 1px solid #A3C293; color: #2C662D; padding: 1em 1.5em; border-radius: 0.285714rem;')
					->p->style('font-size:1.5em;margin:0;')
						->c($this->output->body)
					->end
					->small
						->c('Form submitted: ')->c($this->timestamp->format('n/j/Y g:i A'))
					->end
				->end
			->end
		->end;
	}
}

# Send confirmation messages
class SendConfirmationOutput implements Configurable, Output {
	function __construct($args) {
		$this->from = $args['from'];
		$this->emailField = $args['email-field'];
		$this->subject = $args['subject'];
		$this->body = $args['innerText'];
	}
	function run($data, $page) {
		# Create the email
		$view = new ConfirmationEmail($this, $data['_timestamp']);
		$html = '<!DOCTYPE html>' . Stringifier::stringify($view);

		# ... and send it!
		$mail = new Message();
		$mail
			->setFrom($this->from)
		    ->addTo($data[$this->emailField])
		    ->setSubject($this->subject)
		    ->setHTMLBody($html);

		$mailer = new SmtpMailer(Config::get()['smtp']);

		$mailer->send($mail);

		return $data;
	}
}

# A special output for counting form submissions.
class CounterOutput implements Output {
	function run($data, $page) {
		SubmitCounts::increment($page->id);
		return $data;
	}
}

# Corresponds to the "outputs" element in the configuration file.
# Combines multiple outputs together.
class SuperOutput implements Output, Configurable {

	function __construct($args) {
		$this->outputs = $args['children'];
	}

	# Run all of the outputs, including CounterOutput
	function run($data, $page) {
		(new CounterOutput([]))->run($data, $page);
		foreach ($this->outputs as $output) {
			$data = $output->run($data, $page);
		}
		return $data;
	}

	# Get the storage associated with the form
	function getStorage() {
		$store = null;
		foreach($this->outputs as $output) {
			if($output instanceof Storage) {
				$store = $output;
			}
		}
		if($store) {
			return $store;
		}
		throw new Exception('No Storage found!');
	}
}

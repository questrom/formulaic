<?php

use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;


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
	function getTable($page, $sortBy, $perPage);
}

# MongoDB storage
class MongoOutput implements Configurable, Storage {


	# Construct an object from an element in a configuration file
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
		$this->client = null;
	}

	# Get the associated MongoDB client
	private function getClient() {
		if($this->client === null) {
			$this->client = (new MongoClient($this->server))
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
		$oldData = $data;

		$data = array_map(function($x) {
			if($x instanceof DateTimeImmutable) {
				return new MongoDate($x->getTimestamp());
			} else if($x instanceof FileInfo) {
				throw new Exception('Unexpected file!');
			} else {
				return $x;
			}
		}, $data);

		$collection = $this->getClient();
		$collection->insert($data);

		return $oldData;
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
	function getTable($page, $sortBy, $perPage) {

		$client = $this->getClient();
		$cursor = $client->find()->sort($sortBy);

		if($perPage !== null) {
			# Use intval so PHP will compare values properly elsewhere in the code.
			$max = intval(floor($cursor->count() / $perPage));
		} else {
			# If $perPage is not specified, no pagination will occur.
			$max = 1;
		}

		if($perPage !== null) {
			$cursor->skip(($page - 1) * $perPage);
			$cursor->limit($perPage);
		}

		return [
			'data' => fixMongoDates(array_values(iterator_to_array($cursor))),
			'max' => $max
		];
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
			} else if($x instanceof FileInfo) {
				$ret = $this->s3->putObject(
					S3::inputFile($x->file['tmp_name'], false),
					$this->bucket,
					$x->filename,
					$x->permissions,
					[],
					[ 'Content-Type' => $x->mime ]
				);

				// Based on code from amazon-s3-php-class
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
		$this->secret = Config::get();
	}
	function run($data, $page) {

		# Create an HTML email
		$view = new EmailView($page);
		$view->data = $data;

		$html = '<!DOCTYPE html>' . $view->makeEmailView()->render()->generateString();

		# ... and send it using nette/mail
		$mail = new Message();
		$mail
			->setFrom($this->from)
		    ->addTo($this->to)
		    ->setSubject($this->subject)
		    ->setHTMLBody($html);

		$mailer = new SmtpMailer($this->secret['smtp']);

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
}

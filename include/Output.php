<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;

interface Output {
	function run($data, $page);
}

class MongoOutput implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
	}
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

		$collection = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		$collection->insert($data);

		return $oldData;
	}
}

class S3Output implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->secret =Config::get();
		$this->s3 = new S3($this->secret['s3']['key'], $this->secret['s3']['secret']);
		$this->bucket = $args['bucket'];
	}
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

class CounterOutput implements Output {
	function run($data, $page) {
		$counts = json_decode(file_get_contents('data/submit-counts.json'));
		$counts->{$page->id} = isget($counts->{$page->id}, 0) + 1;
		file_put_contents('data/submit-counts.json', json_encode($counts));
		return $data;
	}
}

class SuperOutput implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->outputs = $args['children'];
	}
	function run($data, $page) {
		(new CounterOutput([]))->run($data, $page);
		foreach ($this->outputs as $output) {
			$data = $output->run($data, $page);
		}
		return $data;
	}
}

class EmailOutput implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->to = $args['to'];
		$this->from = $args['from'];
		$this->subject = $args['subject'];
		$this->secret = Config::get();
	}
	function run($data, $page) {

		$view = new EmailView($page);
		$view->data = $data;

		$html = '<!DOCTYPE html>' . generateString($view->get(new HTMLParentlessContext()));

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
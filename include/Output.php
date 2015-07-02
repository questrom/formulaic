<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;

interface Output {
	function __construct($args);
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
		$this->secret = yaml_parse_file('./config/s3-secret.yml');
		$this->s3 = new S3($this->secret['key-id'], $this->secret['key-secret']);
		$this->bucket = $args['bucket'];
	}
	function run($data, $page) {
		return array_map(function($x) {
			if($x instanceof FileInfo) {


				$ret = $this->s3->putObject(
					S3::inputFile($x->file['tmp_name'], false),
					$this->bucket,
					$x->filename,
					$x->permissions,
					[],
					[
						'Content-Type' => $x->mime
					]
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

class SuperOutput implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->outputs = $args['children'];
	}
	function run($data, $page) {
		foreach ($this->outputs as $output) {
			$data = $output->run($data, $page);
		}
		return $data;
	}
}

use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;

class EmailOutput implements Output, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->to = $args['to'];
		$this->from = $args['from'];
		$this->subject = $args['subject'];
		$this->secret = yaml_parse_file('./config/s3-secret.yml');
	}
	function run($data, $page) {

		$view = new EmailView($page);
		// $view->setPage($page);
		$view->data = $data;
		$html = '<!DOCTYPE html>' . $view->get(new HTMLParentlessContext());

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
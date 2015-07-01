<?php
interface Output {
	function __construct($args);
	function run($data);
}

class MongoOutput extends ConfigElement implements Output {
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
	}
	function run($data) {
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

class S3Output extends ConfigElement implements Output {
	function __construct($args) {
		$this->secret = yaml_parse_file('./config/s3-secret.yml');
		$this->s3 = new S3($this->secret['key-id'], $this->secret['key-secret']);
		$this->bucket = $args['bucket'];
	}
	function run($data) {
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

class SuperOutput extends ConfigElement implements Output {
	function __construct($args) {
		$this->outputs = $args['children'];
	}
	function run($data) {
		foreach ($this->outputs as $output) {
			$data = $output->run($data);
		}
		return $data;
	}
}
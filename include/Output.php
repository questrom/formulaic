<?php
interface Output extends YAMLPart {
	function __construct($args);
	function run($data);
}

class MongoOutput implements Output {
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
	}
	function run($data) {
// echo 'RUN MONGO';
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
	static function fromYaml($elem) {
		return new static($elem->attrs);
	}
}

class S3Output implements Output {
	function __construct($args) {
		$this->secret = yaml_parse_file('./config/s3-secret.yml');
		$this->s3 = new S3($this->secret['key-id'], $this->secret['key-secret']);
		$this->bucket = $args['bucket'];
	}
	function run($data) {
		// echo 'RUN S3';
		$data = array_map(function($x) {
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
		return $data;
	}
	static function fromYaml($elem) {
		return new static($elem->attrs);
	}
}

class SuperOutput implements Output {
	function __construct($args) {
		$this->outputs = $args;
	}
	function run($data) {
		foreach ($this->outputs as $output) {
			$data = $output->run($data);
		}
		return $data;
	}
	static function fromYaml($elem) {
		return new static($elem->children);
	}
}
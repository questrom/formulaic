<?php
interface Output {
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
				$x = $x->value;
				$name = 'test.abc';
				$ret = $this->s3->putObject(S3::inputFile($x['tmp_name'], false), $this->bucket, $name, S3::ACL_PUBLIC_READ);
				return $name;
				// var_dump($ret);
			} else {
				return $x;
			}
		}, $data);
		return $data;
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
}
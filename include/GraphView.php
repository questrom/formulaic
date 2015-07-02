<?php
use Sabre\Xml\XmlDeserializable as XmlDeserializable;

class GraphView implements XmlDeserializable, HTMLComponent {
	use Configurable;

	function __construct($args) {
		$this->title = $args['title'];
		$this->graphs = $args['children'];
	}
	function setPage($page) {
		$this->pageData = $page;

		$mongo = null;
		foreach($page->outputs->outputs as $output) {
			if($output instanceof MongoOutput) {
				$mongo = $output;
			}
		}
		$this->server = $mongo->server;
		$this->database = $mongo->database;
		$this->collection = $mongo->collection;

		foreach($this->graphs as $graph) {
			$graph->setComponent($this->pageData->getByName($graph->name));
		}
	}
	function query($getArgs) {
		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		foreach($this->graphs as $graph) {
			$graph->query($client);
		}
	}
	function get($h) {
		return $h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel("stylesheet")->href("semantic-ui/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->div->class('ui container wide-page')
						->h1
							->t($this->title)
						->end
						->add($this->graphs)
				->end
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('semantic-ui/dist/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
}

abstract class Graph implements XmlDeserializable, HTMLComponent  {
	use Configurable;
	function __construct($args) {
		$this->name = $args['name'];
		$this->label = $args['label'];
	}
	function setComponent($comp) {
		$this->component = $comp;
	}
	function query($client) {
		$results = $client->aggregate(
			[
				'$group' => [
					'_id' => '$' . $this->name,
					'count' => [ '$sum' => 1 ]
				]
			]
		);
		echo $this->name;
		var_dump($results);
	}
}

class PieChart extends Graph {
	function get($h) {
		return $h->div->t('pie')->end;
	}
}

class BarGraph extends Graph {
	function get($h) {
		return $h->div->t('bar')->end;
	}
}
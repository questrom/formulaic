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

		foreach($this->graphs as $index => $graph) {
			$graph->setComponent($this->pageData->getByName($graph->name), $index);
		}
	}
	function query($getArgs) {
		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		$this->totalCount = $client->count();
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
				->link->rel("stylesheet")->href("pizza-master/dist/css/pizza.css")->end
			->end
			->body
				->div->class('ui text container')
						->h1
							->t($this->title)
						->end
						->h3
							->t($this->totalCount . ' total submissions')
						->end
						->add($this->graphs)
				->end
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('pizza-master/dist/js/vendor/dependencies.js')->end
				->script->src('pizza-master/dist/js/pizza.js')->end
				->script->src('semantic-ui/dist/semantic.js')->end
				->script->src('graphs.js')->end
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
	function setComponent($comp, $index) {
		$this->component = $comp;
		$this->id = 'graph-' . $index;
	}
	function query($client) {
		if($this->component instanceof Checkboxes) {
			// to handle array case
			$results = $client->aggregate([
				['$unwind' => '$' . $this->name],
				['$group'  => [
					'_id' => '$' . $this->name, 'count' => [ '$sum' => 1 ] ]
				]
			]);
		} else {
			$results = $client->aggregate(
				[
					'$group' => [
						'_id' => '$' . $this->name,
						'count' => [ '$sum' => 1 ]
					]
				]
			);
		}
		$results = $results['result'];
		// $results = array_combine(
		// 	array_map(function($result) {
		// 		$key = $result['_id'];
		// 		if($key === true) { $key = 'Yes'; }
		// 		if($key === false) { $key = 'No'; }
		// 		if($key === null) { $key = '(None)'; }
		// 		return $key;
		// 	}, $results),
		// 	array_map(function($result) { return $result['count']; }, $results)
		// );
		$this->results = $results;

	}
}

class PieChart extends Graph {
	function get($h) {
		return $h
			->h4->t($this->label)->end
			->ul->data('pie-id', $this->id)
				->add(array_map(function($result) use ($h) {
					$key = $result['_id'];
					if($key === true) { $key = 'Yes'; }
					if($key === false) { $key = 'No'; }
					if($key === null) { $key = '(None)'; }

					return $h
					->li->data('value', $result['count'])->t($key)->end;
				}, $this->results))
			->end
			->div->id($this->id)->end;
			// ->div->t(implode('<br>', $this->results))->end;

	}
}

function kvmap(callable $fn, $array) {
	$result = [];
	foreach($array as $key => $value) {
		$result[$key] = $fn($key, $value);
	}
	return $result;
}

class BarGraph extends Graph {
	function get($h) {
		// see http://bost.ocks.org/mike/bar/2/
		// return $h
		// 	->h4->t($this->label)->end
		// 	->svg->width(420)->height(count($this->results) * 20)
		// 		->add(kvmap(function($index, $result) use($h) {
		// 			return $h
		// 			->g->transform('translate(0, ' . ($index * 20) . ')')
		// 				->rect->width( $result['count'] )->height(20)->end
		// 			->end;
		// 		}, $this->results))
		// 	->end;

		return $h
			->h4->t($this->label)->end
			->ul->data('bar-id', $this->id)
				->add(array_map(function($result) use ($h) {
					$key = $result['_id'];
					if($key === true) { $key = 'Yes'; }
					if($key === false) { $key = 'No'; }
					if($key === null) { $key = '(None)'; }

					return $h
					->li->data('value', $result['count'])->t($key)->end;
				}, $this->results))
			->end
			->div->id($this->id)->end;
	}
}
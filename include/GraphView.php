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
						->div

							->h1->class('ui header')
								->div->class('pull-right ui large label submit-count-label')
									->t($this->totalCount)
									->div->class('detail')->t('total submissions')->end
								->end
								->t($this->title)
							->end
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

			// TODO: remove this...
			$this->results = [];
			return;

			// to handle array case
			$results = $client->aggregate([
				[
					'$unwind' =>
					'$' . $this->name
				],
				['$group'  => [
					'_id' => '$' . $this->name,
					'count' => [ '$sum' => 1 ] ]
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
					'$project' => [
						'name' => '$' . $this->name,
						'value' => [ '$literal' => 1]
					]
				],

				[
					'$group' => [
						'_id' => '$name',
						'count' => [ '$sum' => '$value' ]
					]
				],

				[
					'$group' => [
						'_id' => null,
						'array' => [ '$push' => '$$CURRENT' ],
						'ids' => [
							'$addToSet' => '$_id'
						]
					]
				],
				[
					'$project' => [
						'_id' => 0,
						'array' => '$array',

						'ids' => [ '$setDifference' => [ $this->component->getPossibleValues(), '$ids' ] ]
					]
				],
				[
					'$project' => [
						'_id' => 0,
						'array' => '$array',

						'ids' => ['$map' => [
							'input' => '$ids',
							'as' => 'x',
							'in' => [
								'_id' => '$$x',
								'count' => ['$literal'=>0]
							]
							// ]
						]]
					]
				],
				[
					'$project' => [
						'array' => [
							'$setUnion' => [
								'$array',
								'$ids'
							]
						]
					]
				],
				[
					'$unwind' => '$array'
				],
				['$project' => [
					'_id' => '$array._id',
					'count' => '$array.count'
				]],
				[
					'$sort' => [
						'count' => -1
					]
				],
			);
		}
		$results = $results['result'];

		// var_dump($results);

		// foreach($this->component->getPossibleValues() as $value) {
		// 	$found = false;
		// 	foreach($results as $result) {
		// 		if($result['_id'] === $value) {
		// 			$found = true;
		// 			break;
		// 		}
		// 	}
		// 	if(!$found) {
		// 		$results[] = [
		// 			'_id' => $value,
		// 			'count' => 0
		// 		];
		// 	}
		// }

		$this->results = $results;

	}
}

class PieChart extends Graph {
	function get($h) {
		return $h
			->div->class('ui fluid card')

				->div->class('content')
					->div->class('header')->t($this->label)->end
				->end
				->div->class('content')
					->ul->data('pie-id', $this->id)
						->add(array_map(function($result) use ($h) {

					$key = $result['_id'];

									$hue = floor( hexdec(substr(md5($key), 0, 2))  * (360/256) );

									$color = 'hsl(' . $hue . ', 70%, 50%)';
									if($key === true) { $key = 'Yes'; $color='#21ba45'; }
									if($key === false) { $key = 'No'; $color='#db2828'; }
									if($key === null) { $key = '(None)'; $color='#777'; }

							return $h
							->li->data('value', $result['count'])->style('color: '. $color)->t($key)->end;
						}, $this->results))
					->end
					->div->class('pie-chart')->id($this->id)->end
				->end
			->end;

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

		if(count($this->results) > 0) {
			$max = max(array_map(function($result) {
				return $result['count'];
			}, $this->results));
		} else {
			$max = 0;
		}


		// see http://bost.ocks.org/mike/bar/2/
		return $h
			->div->class('ui fluid card')

				->div->class('content')
					->div->class('header')->t($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('0 0 700 ' . count($this->results) * 30 )->style('background:#fff')
						->add(kvmap(function($index, $result) use($h, $max) {
							$barWidth = ($result['count']/$max) * 500;
							$labelAtRight = $barWidth < 40;

							$key = $result['_id'];

							$hue = floor( hexdec(substr(md5($key), 0, 2))  * (360/256) );

							$color = 'hsl(' . $hue . ', 70%, 50%)';
							if($key === true) { $key = 'Yes'; $color='#21ba45'; }
							if($key === false) { $key = 'No'; $color='#db2828'; }
							if($key === null) { $key = '(None)'; $color='#777'; }

							return $h
							->g->transform('translate(0, ' . ($index * 30) . ')')
								->text
									->style('dominant-baseline:middle;text-anchor:end;')
									->x(140)->y(15)
									->t($key)
								->end
								->rect->width( $barWidth )->y(5)->x(150)->height(20)->fill($color)->end
								->text
									->x(150 + $barWidth + ($labelAtRight ? 2 : -5))
									->y(15)
									->fill($labelAtRight ? 'black' : 'white')
									->style('dominant-baseline:middle;text-anchor:' . ($labelAtRight ? 'start;' : 'end;'))
									->t($result['count'])
								->end
							->end;
						}, $this->results))
					->end
				->end
			->end;
	}
}
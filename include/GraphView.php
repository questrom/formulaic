<?php
use Sabre\Xml\XmlDeserializable as XmlDeserializable;
use \Colors\RandomColor;

class GraphView implements XmlDeserializable, HTMLComponent, View {
	use Configurable;

	function __construct($args) {
		$this->name = $args['name'];
		$this->title = $args['title'];
		$this->graphs = $args['children'];
		$this->type = 'graph';
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
				->link->rel("stylesheet")->href("lib/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->addH(new TopHeader())
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
						->addH( array_map(function($x) { return $x ? $x->get(new HTMLParentlessContext()) : null; }, $this->graphs) )
				->end
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

				['$group'  => [
					'_id' => '$' . $this->name,
					'count' => [ '$sum' => 1 ] ]
				],
				[
					'$sort' => [
						'count' => -1
					]
				]
			);
		}
		$results = $results['result'];

		$ids = array_map(function($x) { return $x['_id']; }, $results);



		foreach(array_diff($this->component->getPossibleValues(), $ids) as $value) {
			$results[] = [
				'_id' => $value,
				'count' => 0
			];

		}

		$this->results = $results;

	}
}

class PieChart extends Graph {
	function get($h) {

		$total = array_sum(array_map(function($result) {
				return $result['count'];
			}, $this->results));

		$lastAngle = -pi() / 2;


		return $h
			->div->class('ui fluid card')
				->div->class('content')
					->div->class('header')->t($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('-900 -600 1800 1200')->style('background:#fff')
						->addH(kvmap(function($index, $result) use($h, $total, &$lastAngle) {

							$key = $result['_id'];

							$percent = ($result['count'] / $total);


							$hue = floor( hexdec(substr(md5($key), 0, 2))  * (360/256) );

							$color = RandomColor::one([
								'luminosity' => 'bright',
								'prng' => function($min, $max) use ($key) {
									return (hexdec(substr(md5($key), 0, 2)) / pow(16, 2))  * ($max - $min) + $min;
								}
							]);


							if($key === true) { $key = 'Yes'; $color='#21ba45'; }
							if($key === false) { $key = 'No'; $color='#db2828'; }
							if($key === null) { $key = '(None)'; $color='#777'; }

							$pct = ($result['count'] / $total) * 2 * pi() + $lastAngle;

							$largeSweep = (($result['count'] / $total) >= 0.5) ? 1 : 0;

							$startX = cos($lastAngle) * 600;
							$startY = sin($lastAngle) * 600;
							$endX = cos($pct) * 600;
							$endY = sin($pct) * 600;

							$path = "M 0 0 L $startX $startY A 600 600 0 $largeSweep 1 $endX $endY L 0 0";

							$lastAngle = $pct;

							return $h
								->path->fill($color)->d($path)->end
								->rect
									->x(-900)->y(-600 + $index * 50 + 10)
									->width(30)->height(30)
									->fill($color)
								->end
								->text
									->style('dominant-baseline:text-before-edge;text-anchor:start;font-size: 40px;')
									->x(-900 + 40)->y(-600 + $index * 50)
									->t($key . ' (' . round($percent * 100, 1) . '%)')
								->end
							;
						}, $this->results))
					->end
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
						->addH(kvmap(function($index, $result) use($h, $max) {
							$barWidth = ($result['count']/$max) * 500;
							$labelAtRight = $barWidth < 40;

							$key = $result['_id'];


							$color = RandomColor::one([
								'luminosity' => 'bright',
								'prng' => function($min, $max) use ($key) {
									return (hexdec(substr(md5($key), 0, 2)) / pow(16, 2))  * ($max - $min) + $min;
								}
							]);

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
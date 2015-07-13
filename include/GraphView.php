<?php
use Sabre\Xml\XmlDeserializable as XmlDeserializable;
use \Colors\RandomColor;

class GraphViewRenderable implements Renderable {
	public function __construct($field, $info) {
		$this->f = $field;
		$this->i = $info;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->f->title)->end
				->link->rel('stylesheet')->href('lib/semantic.css')->end
				->link->rel('stylesheet')->href('styles.css')->end
			->end
			->body
				->addH(new TopHeader())
				->div->class('ui text container')
						->h1->class('ui header')
							->div->class('pull-right ui large label submit-count-label')
								->t($this->f->totalCount)
								->div->class('detail')->t('total submissions')->end
							->end
							->t($this->f->title)
						->end
						->addH( array_map(function($x) {
							return $x['graph']->makeGraphViewPart($x['results']);
						}, $this->i) )
				->end
			->end
		->end;
	}
}

class GraphView implements XmlDeserializable, View {
	use Configurable;
	function makeView($data) {
		$info = [];
		foreach($data as $index => $piece) {
			$info[] = [
				'graph' => $this->graphs[$index],
				'results' => $piece
			];
			$this->graphs[$index]->results = $piece;
		}
		return new GraphViewRenderable($this, $info);
	}

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
			$graph->setComponent($this->pageData->form->getByName($graph->name));
		}
	}
	function query($getArgs) {
		$data = [];
		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		$this->totalCount = $client->count();
		foreach($this->graphs as $graph) {
			$data[] = $graph->query($client);
		}
		return $data;
	}
}

abstract class Graph implements XmlDeserializable, GraphViewPartFactory  {
	use Configurable;
	function __construct($args) {
		$this->name = $args['name'];
		$this->label = $args['label'];
	}
	function setComponent($comp) {
		$this->component = $comp;
	}
	function query($client) {
		if($this->component instanceof Checkboxes) {
			// to handle array case
			$results = $client->aggregate([
				[
					'$unwind' => '$' . $this->name
				],
				[
					'$group'  => [
						'_id' => '$' . $this->name,
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
						'_id' => '$' . $this->name,
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

		$results = $results['result'];
		$ids = array_map(function($x) {
			return $x['_id'];
		}, $results);

		foreach(array_diff($this->component->getPossibleValues(), $ids) as $value) {
			$results[] = [
				'_id' => $value,
				'count' => 0
			];
		}

		foreach($results as $index => &$result) {
			$result['index'] = $index;
		}

		return $results;

	}
}

class PieSlice implements Renderable {
	function __construct($result, $total, $prev) {
		$this->result = $result;
		$this->total = $total;
		$this->h = new HTMLParentlessContext();
		$this->prev = $prev;
		$this->lastAngle = isset($prev) ? $prev->endAngle : -pi()/2;
		$this->endAngle = ($this->result['count'] / $total) * 2 * pi() + $this->lastAngle;

	}
	function render() {
		$key = $this->result['_id'];

		$percent = ($this->result['count'] / $this->total);

		if($key === true) {
			$key = 'Yes';
			$color='#21ba45';
		} else if($key === false) {
			$key = 'No';
			$color='#db2828';
		} else if($key === null) {
			$key = '(None)';
			$color='#777';
		} else {
			$color = RandomColor::one([
				'luminosity' => 'bright',
				'prng' => function($min, $max) use ($key) {
					return (hexdec(substr(md5($key), 0, 2)) / pow(16, 2))  * ($max - $min) + $min;
				}
			]);
		}

		$pct = $this->endAngle;


		$largeSweep = ($percent >= 0.5) ? 1 : 0;

		$startX = cos($this->lastAngle) * 600;
		$startY = sin($this->lastAngle) * 600;
		$endX = cos($pct) * 600;
		$endY = sin($pct) * 600;

		$path = "M 0 0 L $startX $startY A 600 600 0 $largeSweep 1 $endX $endY L 0 0";


		return [
			$this->prev,
			$this->h
			->path->fill($color)->stroke('#000')->{'stroke-width'}('2px')->d($path)->end
			->rect
				->x(-900)->y(-600 + $this->result['index'] * 50 + 10)
				->width(30)->height(30)
				->fill($color)
			->end
			->text
				->style('dominant-baseline:text-before-edge;text-anchor:start;font-size: 40px;')
				->x(-900 + 40)->y(-600 + $this->result['index'] * 50)
				->t($key . ' (' . round($percent * 100, 1) . '%)')
			->end
		];
	}
}

class PieChartRenderable implements Renderable {
	function __construct($label, $results) {
		$this->label = $label;
		$this->results = $results;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		$total = array_sum(array_map(function($result) {
			return $result['count'];
		}, $this->results));

		return $this->h
			->div->class('ui fluid card')
				->div->class('content')
					->div->class('header')->t($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('-900 -600 1800 1200')->style('background:#fff;width:100%;')
						->addH(array_reduce($this->results, function($carry, $result) use($total) {
							return new PieSlice($result, $total, $carry);
						}))
					->end
				->end
			->end;
	}
}

class BarGraphRenderable implements Renderable {
	function __construct($label, $results) {
		$this->label = $label;
		$this->results = $results;
		$this->h = new HTMLParentlessContext();
	}
	function render() {

		if(count($this->results) > 0) {
			$max = max(array_map(function($result) {
				return $result['count'];
			}, $this->results));
		} else {
			$max = 0;
		}


		// see http://bost.ocks.org/mike/bar/2/
		return $this->h
			->div->class('ui fluid card')

				->div->class('content')
					->div->class('header')->t($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('0 0 700 ' . count($this->results) * 30 )->style('background:#fff;width:100%;')
						->addH(array_map(function($result) use($max) {
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

							return $this->h
							->g->transform('translate(0, ' . ($result['index'] * 30) . ')')
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


class PieChart extends Graph {
	function makeGraphViewPart($data) {
		return new PieChartRenderable($this->label, $data);
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
	function makeGraphViewPart($data) {
		return new BarGraphRenderable($this->label, $data);
	}
}
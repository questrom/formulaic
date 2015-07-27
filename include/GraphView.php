<?php
use \Colors\RandomColor;

# This file includes a number of classes related to implementing graph views --
# that is, views which display a series of graphs.

# Displays a graph view
class GraphViewRenderable implements Renderable {
	public function __construct($field, $info) {
		$this->f = $field;
		$this->i = $info;
	}
	function render() {
		return h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->c($this->f->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end
			->end
			->body
				->c(new TopHeader())
				->div->class('ui text container')
						->h1->class('ui header')
							->div->class('pull-right ui large label submit-count-label')
								->c($this->f->totalCount)
								->div->class('detail')->c('total submissions')->end
							->end
							->c($this->f->title)
						->end

						->c( $this->f->totalCount === 0 ?
							h()->h3->class('ui center aligned header')->c('No results found')->end :
						array_map(function($x) {
							return $x['graph']->makeGraphViewPart($x['results']);
						}, $this->i) )
				->end
			->end
		->end;
	}
}

# The logic behind a graph view
# See the ConfigurableView interface in View.php for more info about each method.
class GraphView implements ConfigurableView {

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

	function getIcon() {
		return 'area chart icon';
	}

	function __construct($args) {
		$this->name = $args['name'];
		$this->title = $args['title'];
		$this->graphs = $args['children'];
	}

	function setPage($page) {
		$this->pageData = $page;

		$this->mongo = $page->getMongo();

		$byName = $this->pageData->form->getAllFields();
		foreach($this->graphs as $index => $graph) {
			$graph->setComponent($byName[$graph->name]);
		}
	}

	function query($getArgs) {
		$data = [];

		$this->totalCount = $this->mongo->count();
		if($this->totalCount > 0) {
			foreach($this->graphs as $graph) {
				$data[] = $graph->query($this->mongo);
			}
		}
		return $data;
	}
}

# An abstract class used to implement both pie and bar charts.
abstract class Graph implements Configurable, GraphViewPartFactory  {

	function __construct($args) {
		$this->name = $args['name'];
		$this->label = $args['label'];
	}

	# Set the form field whose data will be displayed.
	function setComponent($comp) {
		$this->component = $comp;
	}

	# Query MongoDB for the relevant data
	function query($mongo) {
		$results = $mongo->getStats($this->component instanceof Checkboxes, $this->name);
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

# An individual slice within a pie chart
class PieSlice implements Renderable {
	function __construct($result, $total, $prev) {
		$this->result = $result;
		$this->total = $total;

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
					# Get the color by hashing the text
					# So it stays the same when the page is refreshed
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

		# Generate the arc
		$path = "M 0 0 L $startX $startY A 600 600 0 $largeSweep 1 $endX $endY L 0 0";


		return [
			$this->prev,
			h()
			->path->fill($color)->stroke('#000')->{'stroke-width'}('2px')->d($path)->end
			->rect
				->x(-900)->y(-600 + $this->result['index'] * 50 + 10)
				->width(30)->height(30)
				->fill($color)
			->end
			->text
				->style('dominant-baseline:text-before-edge;text-anchor:start;font-size: 40px;')
				->x(-900 + 40)->y(-600 + $this->result['index'] * 50)
				->c($key . ' (' . round($percent * 100, 1) . '%)')
			->end
		];
	}
}

# Displays a pie chart
class PieChartRenderable implements Renderable {
	function __construct($label, $results) {
		$this->label = $label;
		$this->results = $results;

	}
	function render() {
		$total = array_sum(array_map(function($result) {
			return $result['count'];
		}, $this->results));

		return h()
			->div->class('ui fluid card')
				->div->class('content')
					->div->class('header')->c($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('-900 -600 1800 1200')->style('background:#fff;width:100%;')
						->c(array_reduce($this->results, function($carry, $result) use($total) {
							return new PieSlice($result, $total, $carry);
						}))
					->end
				->end
			->end;
	}
}

# Displays a bar graph
class BarGraphRenderable implements Renderable {
	function __construct($label, $results) {
		$this->label = $label;
		$this->results = $results;

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
		return h()
			->div->class('ui fluid card')

				->div->class('content')
					->div->class('header')->c($this->label)->end
				->end
				->div->class('content')
					->svg->viewBox('0 0 700 ' . count($this->results) * 30 )->style('background:#fff;width:100%;')
						->c(array_map(function($result) use($max) {
							$barWidth = ($result['count']/$max) * 500;
							$labelAtRight = $barWidth < 40;

							$key = $result['_id'];


							$color = RandomColor::one([
								'luminosity' => 'bright',
								'prng' => function($min, $max) use ($key) {

									# Get the color by hashing the text
									# So it stays the same when the page is refreshed
									return (hexdec(substr(md5($key), 0, 2)) / pow(16, 2))  * ($max - $min) + $min;
								}
							]);

							if($key === true) { $key = 'Yes'; $color='#21ba45'; }
							if($key === false) { $key = 'No'; $color='#db2828'; }
							if($key === null) { $key = '(None)'; $color='#777'; }

							return h()
							->g->transform('translate(0, ' . ($result['index'] * 30) . ')')
								->text
									->style('dominant-baseline:middle;text-anchor:end;')
									->x(140)->y(15)
									->c($key)
								->end
								->rect->width( $barWidth )->y(5)->x(150)->height(20)->fill($color)->end
								->text
									->x(150 + $barWidth + ($labelAtRight ? 2 : -5))
									->y(15)
									->fill($labelAtRight ? 'black' : 'white')
									->style('dominant-baseline:middle;text-anchor:' . ($labelAtRight ? 'start;' : 'end;'))
									->c($result['count'])
								->end
							->end;
						}, $this->results))
					->end
				->end
			->end;
	}
}

# A pie chart itself
class PieChart extends Graph {
	function makeGraphViewPart($data) {
		return new PieChartRenderable($this->label, $data);
	}
}

# A bar graph itself
class BarGraph extends Graph {
	function makeGraphViewPart($data) {
		return new BarGraphRenderable($this->label, $data);
	}
}
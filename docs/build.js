// Based on metalsmith-navigation example

var path = require('path'),
	Handlebars = require('handlebars'),
	metalsmith = require('metalsmith'),
	markDown = require('metalsmith-markdown'),
	templates = require('metalsmith-templates'),
	assets = require('metalsmith-assets'),
	navigation = require('metalsmith-navigation');

Handlebars.registerHelper('relative_path', function(current, target) {
	if(!current) {
		current = '.';
	}
	if(!current || !target){
		return '';
	}
	current = path.normalize(current).slice(0);
	target = path.normalize(target).slice(0);
	current = path.dirname(current);
	return path.relative(current, target).replace(/\\/g, '/');
});

// See http://stackoverflow.com/questions/8853396/logical-operator-in-a-handlebars-js-if-conditional
Handlebars.registerHelper('ifCond', function(v1, v2, options) {
  if(v1 === v2) {
	return options.fn(this);
  }
  return options.inverse(this);
});

var metalsmith = metalsmith(__dirname)
	.clean(true)
	.metadata({
		partials: {
			breadcrumbs: '_breadcrumbs',
			nav_global : '_nav_global',
			nav__children: '_nav__children',
		}
	})
	.use(markDown())
	.use(navigation({
		primary: {
			sortBy: 'nav_sort',
			filterProperty: 'nav_groups',
			sortByNameFirst: true
		}
	}, {
		navListProperty: 'navs',
		pathProperty: 'def'
	}))
	.use(templates({
		engine: 'handlebars'
	}))
	.use(assets({
		source: './assets',
		destination: './assets'
	}))
	.build(function(err) {
		if (err) throw err;
	});

'use strict';

var path        = require('path');

var Handlebars  = require('handlebars');

var metalsmith  = require('metalsmith');

var markDown    = require('metalsmith-markdown');
var templates   = require('metalsmith-templates');
var assets      = require('metalsmith-assets');

var navigation  = require('metalsmith-navigation');
// var navigation = require('../../lib/index.js');

var navConfigs = {
    primary:{
        sortBy: 'nav_sort',
        filterProperty: 'nav_groups',
                sortByNameFirst: true
    },
    footer: {
        sortBy: 'nav_sort',
        filterProperty: 'nav_groups',
                sortByNameFirst: true
    }
};

var navSettings = {
    navListProperty: 'navs',
    pathProperty: 'def'
};

var navTask = navigation(navConfigs, navSettings);

var assetsTask = assets({
    source: './assets',
    destination: './assets'
});

var markDownTask = markDown({
    // breaks: true
});

var templatesTask = templates({
    engine: 'handlebars'
});

var meta = {
    title: 'Metalcorp',
    description: 'Your full service solution.',
    // used by metalsmith-templates
    partials: {
        breadcrumbs: '_breadcrumbs',

        nav_global : '_nav_global',


        nav__children: '_nav__children',
    }
};

var relativePathHelper = function(current, target) {
    // current = '';
    // console.log(current, target);
    // normalize and remove starting slash from path

    if(!current) {
        current = '.';
    }
    // console.log(current, target);

    if(!current || !target){
        return '';
    }
    current = path.normalize(current).slice(0);
    target = path.normalize(target).slice(0);
    current = path.dirname(current);
    return path.relative(current, target).replace(/\\/g, '/');
};

Handlebars.registerHelper('relative_path', relativePathHelper);

// http://stackoverflow.com/questions/8853396/logical-operator-in-a-handlebars-js-if-conditional
Handlebars.registerHelper('ifCond', function(v1, v2, options) {
  if(v1 === v2) {
    return options.fn(this);
  }
  return options.inverse(this);
});

var metalsmith = metalsmith(__dirname)
    .clean(true)
    .metadata(meta)
    .use(markDownTask)
    .use(function (files, metalsmith, done){
        for (var file in files) {
            // console.log(files[file])
          // files[file].contents = new Buffer(files[file].contents.toString().replace(/<hr>/g, '<div class="ui divider"></div>'))
        }
        done();
      })
    .use(navTask)
    .use(templatesTask)
    .use(assetsTask)
    .build(function(err) {
        if (err) throw err;
    });

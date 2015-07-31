---
title: Future Directions
template: page.html
nav_groups:
  - primary
nav_sort: 5
---

This page presents some ideas for new features that could be added to make Formulaic even more useful, stable, and secure.

### Unit Tests

The program as it stands does not include unit tests, as the behavior of the app was rapidly changing during development. Now, however, it may be desirable to add some unit tests (especially for the less IO-heavy parts of the app.) The best option for doing this seems to be [testmore](https://github.com/shiflett/testmore), which is less bloated than things like PHPUnit.

### Linting

Currently, I have tried linting the app using both PHPCS and PHPMD; the [Goldstandard](https://github.com/jakoch/Goldstandard-for-PHP) standard has been particularly useful, as it checks for the use of string functions that aren't Unicode-safe.

However, I have encountered difficulty in setting up these tools to work well for this project; PHPCS in particular is very hard to configure. Therefore, I have not included a ruleset or standard with this project. I encourage the reader only to maintain the existing style wherever possible, and to use tabs instead of spaces for indentation :)

In the future, however, creating such a standard might be useful for this and for other projects.

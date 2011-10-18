poform - PHP 5 object to form generator
=======================================

Ronaldo Barbachano Oct 2011

 -- Currently in Proof of concept stage
 
 -- Idea is to create a library that will automatically generate a form to manipulate
 a php object. Why? Ultimately to tie this in with my other libraries (couchCurl/html5_core)
 to create a couch/php framework. This component would aid the the manipulation of 
 json objects (when using jser)
 
 For now only generates a very simple html form, but will be rapidly developed..

I will support selection lists, checkboxes, radio boxes etc, and the new html5
form elements when tied in to html5_core.


To do
=====

Process the _POST object to render from user input.

Integrate html5_core to handle html rendering.

Define classes, or design pratices to either automatically or not, define form behavior

Create rhobust (and almost automatic support) for generation of html5 form tags (checkboxes,radios, etc.)

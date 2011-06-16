=== Plugin Name ===
Contributors: shauno
Donate link: http://shauno.co.za/donate/
Tags: nextgen-gallery, nextgen, gallery, voting, rating, ratings, nextgen-gallery-voting
Requires at least: 2.9.1
Tested up to: 3.1.3
Stable tag: 1.8.1

This plugin adds the ability for users to vote on NextGEN Galleries and Images. A few basic options give the ability to limit who can vote on what.

== Description ==

**Please use version 1.5 for version of NGG LOWER than 1.7.x.  Please use version 1.6 or greater for versions of NGG >= 1.7.x**

This plugin adds the ability for users to vote on NextGEN Galleries and Images. A few basic options give the ability to limit who can vote on what.

NGG Voting was inspired by a request from [Troy Schlegel of Schlegel Photography](http://www.schlegelphotography.com/).  Please read the FAQ for more info on how it works.

**QUESTIONS ASKED ON THE FORUM THAT ARE IN THE FAQ WILL NOT BE ANSWERED BY ME**

== Frequently Asked Questions ==

= In a nutshell, what is this? =
This plugin adds options that can allow your users to vote on (more like rate) your Galleries and Images. There are options to limit which Gallery/Image to allow voting on, if the user needs to be registered and logged in, if they can vote more that once, and if they can see the current results.

= How do I add the voting form? =
For Galleries it's easy.  You just need to enable voting on the gallery, and the voting form will automatically be appended to the gallery.  There is some basic styling, but the markp has got some classes and ids that you should easily be able to hook into with your own stylesheets to make suit your blog.

= Ok, and how do I make the voting form appear for images? =
For images there's an extra step.  First you still need to enable voing on the specific images you want.  Then you need to add a tag to the gallery file of NextGEN.  I'm going to use NextGEN version 1.3.6 to as the example, but it should be pretty much the same for all 'newish' versions:
Between line 38 and line 50 in `/nextgen-gallery/view/gallery.php` is the loop that shows each image in a specific gallery.  You need to add the following tag anywhere in that loop: `<?php echo nggv_imageVoteForm($image->pid); ?>`.
That will output the vote form where you put it.  Personally I like to place it on a new line after the close `<a>` tag (new line created 45)

= Ew, that looks hideous =
Alas, it is true.  I have a pretty limited eye for design and layout.  But in the same way as the gallery voting form, there is plenty of place for you to work your CSS magic and make the voting forms look like you want.

= Where are the results =
Under the Gallery or Image options, the current average vote show along with how many votes have been cast.  Click on the number of votes cast to show more info on those votes.

== Installation ==

1. Unzip the plugin to your `wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to 'Manage Gallery' in NextGEN, and select a gallery to see the new options

== Changelog ==

= 1.8 =
* Made compatible with NGG 1.8.0 (Thanks to csillery for reporting the issue)

= 1.8.1 =
* Fixed a bug stopping votes saving if MySQL was in STRICT MODE
* Fixed a bug showing floating numbers for low rated images
* Added report to list top images

= 1.7.2 =
* Added 'Voting Type' default when creating a new gallery

= 1.7.1 =
* Fixed a bug stopping voting working when including the gallery with the [nggtags] shortcode (Thanks migf1 for finding and reporting it)

= 1.7 =
* Made 'like/dislike' and 'star' ratings use ajax to cast votes.  But it will fall back if javascripti is not enabled

= 1.6.2 =
* I screwed up the backwards compatibility, sorry.  Use 1.5 for < NGG1.7

= 1.6.1 =
* Made it backwards compatible with NGG 1.6.x and lower. Should have been done with the last update, but I was spaced on pain meds

= 1.6 =
* Made this plugin compatibile with NextGEN Galley 1.7.x and greater, which breaks compatibility with lower versions of NGG

= 1.5 =
* Added a new type of voting, the "Like / Dislike"

= 1.4 =
* Added the ability to set default voting options for new Galleries and Images

= 1.3.1 =
* Fixed a broken close label tag that caused some issues with the drop down voting (Thanks to Mae Paulino for pointing it out)

= 1.3 =
* Fixed a bug the directed users to a 404 if using star ratings with pretty URLs enabled

= 1.2 =
* Added the ability to choose to vote using 5 star rating
* Removed hook that was creating a blank admin menu item in the Wordpress backend

= 1.1 =
* Added voting to images
* Fixed bug that broke the admin layout in Internet Explorer

= 1.0 =
* Initial Release

## reclaim social media proxy scripts

these are some php scripts that connect to the APIs of a couple of social networks and publish the data as RSS. these RSS feeds can then, for example, be parsed by [feedworpress](http://wordpress.org/plugins/feedwordpress/), which publishes a wordpress article for each item.

example: twitter.php authenticates the user that is configured in config.php at api.twitter.com and gets a json of this users timeline. the timeline is then re-published as RSS.

this works more or less for 
* twitter
* facebook
* google+
* instagram
* pinterest
* flickr
* adn
* quote.fm

of course sites like pinboard.in, that offer user’s timelines as RSS don’t need a proxy script and can be parsed by feedwordpress right away.



<?php
$subject = '<div class="test" data-foreach="titles">{$title}</div>';

$pattern = '/<(?<HtmlTag>[\w]+)[^>]*\sdata-foreach=(?<Quote>[\"\']?)[\w]+(?(Quote)\k<Quote>)[^>]*?(/>|>((?<Nested><\k<HtmlTag>[^>]*>)|</\k<HtmlTag>>(?<-Nested>)|.*?)*</\k<HtmlTag>>)/';

$pattern = '/\<((?<HtmlTag>[\w]+)[^\>]*)\>([^\<]*)\<\/\k<HtmlTag>\>/';

$matches = null;
$num = preg_match_all($pattern, $subject, $matches);

print_r($matches);


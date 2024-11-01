<?php
/*
	Template Name: Web Clap
*/

/* web拍手用の空のテンプレート */
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>
<!--
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
-->	
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<style type="text/css">
<!--
body {
	margin-top: 40px;
}
#webclap {
	font-size: 12px;
	text-align: center;
	
	width: 480px;
	margin: 0 auto;
	padding: 2em 0 4em 0;
	border	: 1px solid #AAA;
	border-radius: 4px;  
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
}
#webclap p {
	margin-bottom: 3em;
}
textarea {
	border: 1px solid #AAAAAA;
	margin-bottom: 2px;
	padding: 2px 4px;
	border-radius: 4px;  
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
}
-->
	</style>
</head>
<body <?php body_class(); ?>>

	<div id="webclap">
		<?php
			the_post();
			the_content();
		?>
	</div>

</body>
</html>

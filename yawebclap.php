<?php
/*====================================================================================
Plugin Name: Yet Another WebClap for WordPress
Plugin URI: about:_blank
Description: Webclap for wordpress. 1. add button by webclap widget, 2. make webclap form by shortcode [webclap], 3. view analysis from dashboard.
Author: __trial
Version: 0.2
Author URI: http://trial-run.net/
Text Domain: yaweb
====================================================================================*/

/* 多言語対応 */
load_textdomain('yaweb', plugin_dir_path(__FILE__).'yaweb'.'-'.get_locale().'.mo');

/*==============================================================
	web拍手へのリンク、ボタンを表示
	引数 text, image, width, height, alt
================================================================*/
function webclap_button($url, $text, $image) {
	/* 引数が null の場合に初期化 */
	if ($text==null) $text = __('webclap', 'yaweb');
	if ($image==null) $image = '';
	if ($url==null) $url = '';
	/* タグを生成 */
	if ( $image!='' && $size = getImageSize($image) ) {
		echo '<a href="'.$url.'"><img src="'.$image.'" '.$size[3].' alt="'.$text.'"></a>';
	} else {
		echo '<a href="'.$url.'">'.$text.'</a>';
	}
}
/* shortcode */
function webclap_button_sc($atts) {
	extract( shortcode_atts( array(
		'text' => __('webclap', 'yaweb'),
		'image' => '',
		'url' => '',
	), $atts ) );
	webclap_button($url, $text, $image);
}
add_shortcode('webclap_button', 'webclap_button_sc');

/*==============================================================
	グローバル変数と関数
================================================================*/
/* web拍手ログ */
function webclap_LogTable() {
	global $table_prefix;
	return $table_prefix."Webclap";
}
/* web拍手コメント */
function webclap_CommentTable() {
	global $table_prefix;
	return $table_prefix."Webclap_Comments";
}
/* プラグインのURL */
$my_plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));

/*==============================================================
	データベースからの取得
================================================================*/
/* 今日の拍手数を取得 */
function get_wchitstoday() {
	global $wpdb;
	$today = date_i18n("Y-m-d");
	$sql = "SELECT COUNT(1) FROM " . webclap_logTable() . " WHERE timestamp >= '{$today}'";
	return $wpdb->get_var($sql);
}
/* 昨日の拍手数を取得 */
function get_wchitsyesterday() {
	global $wpdb;
	$today = date_i18n("Y-m-d");
	$yesterday = date_i18n("Y-m-d", date_i18n("U")-86400);
	$sql = "SELECT COUNT(1) FROM " . webclap_logTable() . " WHERE timestamp >= '$yesterday' AND timestamp < '$today'";
	return $wpdb->get_var($sql);
}
/* 週間の拍手数を取得 */
function get_wchits7days() {
	global $wpdb;
	$sevendaysago = date_i18n("Y-m-d", date_i18n("U")-86400*7);
	$sql = "SELECT COUNT(1) FROM " . webclap_logTable() . " WHERE timestamp >= '{$sevendaysago}'";
	return $wpdb->get_var($sql);
}
/* 今月の拍手数を取得 */
function get_wchitsmonth() {
	global $wpdb;
	$month = date_i18n("Y-m-1");
	$sql = "SELECT COUNT(1) FROM " . webclap_logTable() . " WHERE timestamp >= '{$month}'";
	return $wpdb->get_var($sql);
}
/* 合計の拍手数を取得 */
function get_wchitstotal() {
	global $wpdb;
	$sql = 'SELECT COUNT(1) FROM ' . webclap_logTable();
	return $wpdb->get_var($sql);
}
/* 今日の時間帯別の拍手数を取得 */
function get_wchourlystats_today() {
	global $wpdb;
	$today = date_i18n("Y-m-d");
	$sql = "SELECT
			HOUR(timestamp) AS label,
			HOUR(timestamp) AS label2,
			COUNT(1) AS amount
			FROM " . webclap_logTable();
	$sql .= " WHERE timestamp >= '{$today}' GROUP BY label";
	return $wpdb->get_results($sql);
}
/* 任意の日付の時間帯別の拍手数を取得（引数：現在の日付との差分） */
function get_wchourlystats($diff) {
	global $wpdb;
	$head = date_i18n("Y-m-d", date_i18n("U")-86400*($diff-1));
	$tail = date_i18n("Y-m-d", date_i18n("U")-86400*$diff);
	$sql = "SELECT
			HOUR(timestamp) AS label,
			HOUR(timestamp) AS label2,
			COUNT(1) AS amount
			FROM " . webclap_logTable();
	$sql .= " WHERE timestamp >= '$tail' AND timestamp < '$head' GROUP BY label";
	return $wpdb->get_results($sql);
}
/* 任意の月次の日付別の拍手数を取得（引数：現在の月との差分） */
function get_wcdailystats($diff) {
	global $wpdb;
	$head = date_i18n("Y-m-01",strtotime("-".($diff-1)." month"));
	$tail = date_i18n("Y-m-01",strtotime("-".$diff." month"));
	$sql = "SELECT
			DAYOFMONTH(timestamp) AS label,
			DAYOFMONTH(timestamp) AS label2,
			COUNT(1) AS amount
			FROM " . webclap_logTable();
	$sql .= " WHERE timestamp >= '$tail' AND timestamp < '$head' GROUP BY label";
	return $wpdb->get_results($sql);
}

/*==============================================================
	プラグインの初期化、起動時にデータベースにテーブルを生成
================================================================*/
function my_activation(){
	global $wpdb;
	/* web拍手ログ */
	if ($wpdb->get_var("show tables like '".webclap_LogTable()."'") != webclap_LogTable()) {
		$sql = "CREATE TABLE " . webclap_LogTable() . " (
			id INTEGER NOT NULL AUTO_INCREMENT,
			IP VARCHAR(16) NOT NULL,
			timestamp DATETIME NOT NULL,
			UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	/* コメントログ */
	if ($wpdb->get_var("show tables like '".webclap_CommentTable()."'") != webclap_CommentTable()) {
		$sql = "CREATE TABLE " . webclap_CommentTable() . " (
			id INTEGER NOT NULL AUTO_INCREMENT,
			IP VARCHAR(16) NOT NULL,
			timestamp DATETIME NOT NULL,
			content TEXT NOT NULL,
			UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

register_activation_hook(__FILE__, 'my_activation');

/*=================================================================
	管理画面
===================================================================*/
/* サイドメニューの「ダッシュボード」下に設定を追加 */
add_action('admin_menu', 'my_plugin_menu');
function my_plugin_menu() {
	add_submenu_page('index.php', __('yaWebclap analysis', 'yaweb'), __('yaWebclap', 'yaweb'), 'manage_options', 'yawebclap', 'my_plugin_options');
}
/* 管理メニューっつーか、解析画面 */
function my_plugin_options() {
	global $wpdb;
	
	/* コメントの構造体、$comments[0]->content でコメントといった具合、id,IP,timestamp,content */
	$sql = "SELECT * FROM " . webclap_CommentTable() . " ORDER BY timestamp DESC";
	$comments = $wpdb->get_results($sql);
	/* プラグインの管理メニューへのパスを取得（2つ目以降の） */
	$url = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
	$pos = strpos($url,'&');
	if ($pos!='') $url = substr($url,0,strpos($url,'&'));
	/* urlから引数を取得、日付の差 $date_pos、月数の差 $month_pos、コメントのページ数 $comments_pos を求める */
	$date_pos = 1;				// 初期値は1日前
	$month_pos = 0;
	$comments_pos = 0;
	if (isset($_GET['date'])) {
		$date = $_GET['date'];
		$date = strtotime(substr($date,0,2) . '-' . substr($date,2,2) . '-' . substr($date,4));
		$date_pos = (int)( ( date_i18n("U") - $date ) / (3600*24) );
	}
	if (isset($_GET['month'])) {
		$month = $_GET['month'];
		$month_pos = ( substr(date_i18n('ym'),0,2) - substr($month,0,2) )*12 + substr(date_i18n('ym'),2) - substr($month,2);
	}
	if (isset($_GET['comment'])) {
		$comments_pos = $_GET['comment'];
	}
	
	/* スタイルの基本値を定義 */
	$td_style = 'border:1px solid #AAA; padding: 1px 4px;';
	/* 以下 HTML */
	echo '<div>';
	
	/* 拍手ログから解析結果を表示 */
	echo '<h3>'.__('Analysis Results','yaweb').'</h3>
		<table><tr><td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('Total', 'yaweb').'</td>
		<td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('Today\'s', 'yaweb').'</td>
		<td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('Yesterday\'s', 'yaweb').'</td>
		<td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('Last 7 days', 'yaweb').'</td>
		<td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('This month', 'yaweb').'</td></tr>';
	echo '<tr><td style="'.$td_style.'">'.get_wchitstotal().'</td>
		<td style="'.$td_style.'">'.get_wchitstoday().'</td>
		<td style="'.$td_style.'">'.get_wchitsyesterday().'</td>
		<td style="'.$td_style.'">'.get_wchits7days().'</td>
		<td style="'.$td_style.'">'.get_wchitsmonth().'</td></tr></table>';
	
	/* 今日の拍手履歴を表示 */
	echo '<h3>'.__('Today\'s analysis log', 'yaweb').'</h3>';
	renderstats(get_wchourlystats_today(), 24);
	
	/* 昨日の拍手履歴を表示 / 任意の日付の拍手履歴を表示 */
	if ($date_pos==1) {
		echo '<h3>'. __('Yesterday\'s analysis log', 'yaweb') .'</h3>';
	} else {
		echo '<h3>'. sprintf( __('%d/%d \'s analysis log', 'yaweb'), ltrim(date_i18n('m',$date),'0'), ltrim(date_i18n('d',$date),'0') ) .'</h3>';
	}
	/* 1日前〜14日前までの拍手履歴へのリンクを標示 */
	echo '<div style="font-size:10px;padding-left:40px;">';
	for($i=1; $i<=14; $i++) {
		$date = date_i18n("ymd",date_i18n("U")-86400*$i);
		if ($i==$date_pos) {
			echo '<span style="margin:0 4px;">' . sprintf( __('%d/%d', 'yaweb'), ltrim(substr($date,2,2),'0'), ltrim(substr($date,4),'0') ) . '</span>';
		} else {
			echo '<span style="margin:0 4px;"><a href="' . $url . '&date=' . $date . '">' . sprintf( __('%d/%d', 'yaweb'), ltrim(substr($date,2,2),'0'), ltrim(substr($date,4),'0') ) . '</a></span>';
		}
	}
	echo '</div>';
	renderstats(get_wchourlystats($date_pos), 24);
	
	/* 今月の拍手履歴を標示 / 任意月の拍手履歴を標示 */
	if ($month_pos==0) {
		echo '<h3>'. __('This month\'s analysis log', 'yaweb') .'</h3>';
	} else {
		echo '<h3>'. sprintf( __('20%d.%02d \'s analysis log', 'yaweb'), ltrim(substr($month,0,2),'0'), ltrim(substr($month,2),'0') ) .'</h3>';
	}
	/* 1ヶ月前〜12ヶ月前までの拍手履歴へのリンクを標示 */
	echo '<div style="font-size:10px;padding-left:40px;">';
	for($i=0; $i<12; $i++) {
		$date = date_i18n("ym",strtotime("-".$i." month"));
		if ($i==$month_pos) {
			echo '<span style="margin:0 4px;">' . sprintf(__('20%d.%02d','yaweb'), substr($date,0,2), ltrim(substr($date,2),'0') ) . '</span>';
		} else {
			echo '<span style="margin:0 4px;"><a href="' . $url . '&month=' . $date . '">' . sprintf(__('20%d.%02d','yaweb'), substr($date,0,2), ltrim(substr($date,2),'0') ) .'</a></span>';
		}
	}
	echo '</div>';
	renderstats(get_wcdailystats($month_pos), date_i18n('d',strtotime(date_i18n('y-m-01',strtotime("-".($month_pos-1)." month")))-3600));
	
	/* 拍手コメントのデータベースから色々と取得 */
	$comments_disp = 20;		// コメントの表示件数（暫定）
	$comments_start_pos = nrange( 0, $comments_pos-9, (int)(count($comments)/$comments_disp-20) );		// 表示されるコメントログへのリンクの最小値
	echo '<h3>'.__('Comment log', 'yaweb').'<a name="comlog">&nbsp;</a></h3>';
	/* << ... [21] [22] [23] [24] [25] [26] ... >> */
	echo '<div style="padding-left:40px;">';
	if ( $comments_start_pos!=0 ) {
		echo '<span style="margin:0 4px;">' . '<a href="' . $url . '&comment=' . 0 . '">&lt;&lt;</a>' .'</span><span style="margin:0 4px;">...</span>';
	}
	for( $i=$comments_start_pos; $i<count($comments)/$comments_disp&&$i<$comments_start_pos+20; $i++) {
		if ($i==$comments_pos) {
			echo '<span style="margin:0 4px;">[' . $i . ']</span>';
		} else {
			echo '<span style="margin:0 4px;"><a href="' . $url . '&comment=' . $i . '#comlog">[' . $i . ']</a></span>';
		}
	}
	if ( $i<count($comments)/$comments_disp ) {
		echo '<span style="margin:0 4px;">...</span><span style="margin:0 4px;">' . '<a href="' . $url . '&comment=' . (int)(count($comments)/$comments_disp-1) . '">&gt;&gt;</a>' .'</span>';
	}
	echo '</div>';
	echo '<table><tr><td style="'.$td_style.'width: 3em;background-color:#EEE;">No.</td>
			<td style="'.$td_style.'width: 10em;background-color:#EEE;">'.__('IP address', 'yaweb').'</td>
			<td style="'.$td_style.'width: 8em;background-color:#EEE;">'.__('date', 'yaweb').'</td>
			<td style="'.$td_style.'width: 48em;background-color:#EEE;">'.__('comment', 'yaweb').'</td></tr>';
	for($i=$comments_disp*$comments_pos; $i<count($comments)&&$i<$comments_disp*($comments_pos+1); $i++) {
		echo '<tr><td style="'.$td_style.'">' . ($i+1) . '</td>
			<td style="'.$td_style.'">' . $comments[$i]->IP. '</td>
			<td style="'.$td_style.'">' . date_i18n('m.d H:i', strtotime($comments[$i]->timestamp)). '</td>
			<td style="'.$td_style.'">' . $comments[$i]->content. '</td></tr>';
	}
	echo '</table>';
	
	/* 拍手ログのデータベースから色々と取得 */
	/* web拍手の構造体、$results[0]->IP でコメントといった具合、id,IP,timestamp */
	if(0) {
		$sql = "SELECT * FROM " . webclap_LogTable() . " ORDER BY timestamp DESC";
		$results = $wpdb->get_results($sql);
		echo '<h3>web拍手 ログ</h2>
			<table><tr><td style="'.$td_style.'width: 10em;background-color:#EEE;">IPアドレス</td>
			<td style="'.$td_style.'width: 8em;background-color:#EEE;">日時</td></tr>';
		/* 最大で 10件まで IP付きでログを表示 */
		for($i=0; $i<count($results)&&$i<50; $i++) {
			echo '<tr><td style="'.$td_style.'">' .$results[$i]->IP. '</td>
			<td style="'.$td_style.'">' . date_i18n('y.m.d H:i', strtotime($results[$i]->timestamp)). '</td></tr>';
		}
		echo '</table>';
	}
	echo '</div>';
}

/* グラフを作成、データセットと、表の項目数（24時間、31日） */
function renderstats($rows, $size) {
	$td_style = 'border:1px solid #AAA; padding: 1px 4px;text-align:center;width:20px;';
	$url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));	// graph.png 用
	$pos = 0;				// データセットの位置？
	$max = 0;				// 拍手数の最大値
	for ( $i=0;$i<count($rows);$i++ ) {
		if ( $max<$rows[$i]->amount ) {
			$max = $rows[$i]->amount;
		}
	}
	echo '<table><tr>';
	for (($size==24?$i=0:$i=1); ($size==24?$i<$size:$i<=$size);$i++) {
		if ($rows[$pos]->label==$i && count($rows)!=0) {
			echo '<td style="'.$td_style.'height:110px;vertical-align:bottom;color:#666;font-size:10px">'
					. ($rows[$pos]->amount) . '<br />
					<img src="'.$url.'/graph.png" width="8px" height="'. (int)(80*$rows[$pos]->amount/$max) .'" alt="plot" />
				</td>';
			$pos++;
		} else {
			echo '<td style="'.$td_style.'height:110px;vertical-align:bottom;color:#CCC;">0</td>';
		}
	}
	echo '<tr>';
	for ($i=0;$i<$size;$i++) {
			echo '<td style="'.$td_style.'color:#444;font-size:10px;padding:1px 2px;background-color:#EEE;">';
			if ($size==24) {
				echo sprintf('%d%s', $i, get_locale()=='ja'?'時':'');
			} else {
				echo sprintf('%d%s', $i+1, get_locale()=='ja'?'日':'');
			}
			echo '</td>';
	}
	echo '</tr></table>';
}
/* 値 $num が $low 〜 $high の範囲内か、範囲内に修正 */
function nrange( $low, $num, $high) {
	if ( $high<$low ) $high = $low;
	return $num>$high ? $high : ( $num<$low ? $low : $num) ;
}

/*==============================================================
	データベースへのログの追加（ウェブ拍手画面から参照）
================================================================*/
function webclap_add() {
	global $wpdb;
	/* ステータスの取得 */
	$timestamp = gmdate("Y-m-d H:i:s", time() + ( get_option('gmt_offset') * 60 * 60 ));
	$remoteaddr = 'unknown';
	if ($_SERVER['REMOTE_ADDR']) {
		$remoteaddr = $_SERVER['REMOTE_ADDR'];
	}
	/* sql文の生成とクエリ投げ */
	$sql = "INSERT INTO " . webclap_LogTable();
	$sql .= " (IP, timestamp) VALUES (";
	$sql .= "'" . $wpdb->escape( $remoteaddr ) . "',";
	$sql .= "'" . $timestamp . "')";
	$results = $wpdb->query($sql);
	/* コメントが付与されていた場合、そっちの処理  */
	$text = htmlspecialchars($_POST["message"]);
	if ($text!="") {
		$sql = "INSERT INTO " . webclap_CommentTable();
		$sql .= " (IP, timestamp, content) VALUES (";
		$sql .= "'" . $wpdb->escape($remoteaddr) . "',";
		$sql .= "'" . $timestamp . "',";
		$sql .= "'" . $text . "')";
		$results = $wpdb->query($sql);
	}
}

/*=================================================================
	表示画面（固定ページからショートコードで参照 [webclap] ）
	固定ページのテンプレートを webclap.php として自作するとヨシ
===================================================================*/
function webclap_form($atts) {
	/* 拍手回数をチェック */
	$count = $_POST["count"];
	if ($count=='') {
		$count = 0;
	}
	if ($count<9) {
		/* フォームを作成 */
		$html.= '<!-- '.__('Comment Form', 'yaweb').' -->';
		$html.= '<form action="" method="POST">';
		$html.= '<input type="submit" value="'.__('Send more', 'yaweb').'" id="webclap-submit"><br />';
		$html.= '<input type="hidden" name="count" value="'.($count+1).'"><br />';
		$html.= '<textarea name="message" rows="3" cols="50" value="" id="webclap-textarea"></textarea>';
		$html.= '</form>';
		$html.= '<!-- '.__('Comment Form', 'yaweb').' -->';
		/* 拍手結果（拍手とコメントを別個に）をデータベースに送信 */
		webclap_add();
	/* 拍手回数が 10回以上の例外処理 */
	} else {
		$html = '<p>'.__('Thank you for your webclaps!', 'yaweb').'</p>';
	}
	return $html;
}
add_shortcode('webclap', 'webclap_form');

/*==================================================================
	web拍手 ウィジェットを定義
====================================================================*/

class webclap_widget extends WP_Widget {
	/* コンストラクタ */
	function webclap_widget() {
		$widget_ops = array('classname' => 'widget_yawebclap', 'description' => __('set webclap button', 'yaweb') );
		$this->WP_Widget('webclap', __('webclap', 'yaweb'), $widget_ops);
	}
	
	/* 管理画面入力フォーム */
	function form( $instance ) {
		/* $instanceから現在の設定を読み込み */
		$title = esc_attr( $instance['title'] );
		$url = esc_attr( $instance['url'] );
		$image = esc_attr( $instance['image'] );
		$text = esc_attr( $instance['text'] );
		?>
		<!--Form-->
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'yaweb'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('url'); ?>"><?php _e('Link to (url):', 'yaweb'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" type="text" value="<?php echo $url; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Image (url):', 'yaweb'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>" type="text" value="<?php echo $image; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text:', 'yaweb'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>" type="text" value="<?php echo $text; ?>" />
		</label>
		</p>
		<!--/Form-->
		<?php
	}
	/* 登録内容の表示 */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$url = apply_filters( 'widget_title', $instance['url'] );
		$image = apply_filters( 'widget_title', $instance['image'] );
		$text = apply_filters( 'widget_title', $instance['text'] );
		
		
		if ($title=='') $title = __('webclap', 'yaweb');
		if ($text=='') $text = __('webclap', 'yaweb');
		
		echo $before_widget;
		echo $before_title . $title . $after_title;
		if ( $image!='' && $size = getImageSize($image) ) {
			echo '<a href="'.$url.'"><img src="'.$image.'" '.$size[3].' alt="'.$text.'"></a>';
		} else {
			echo '<a href="'.$url.'">'.$text.'</a>';
		}
		echo $after_widget;
	}
	/* 登録内容のアップデート */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['url'] = strip_tags($new_instance['url']);
		$instance['image'] = strip_tags($new_instance['image']);
		$instance['text'] = strip_tags($new_instance['text']);
		return $instance;
	}
}
/* ウィジェットの登録 */
function webclap_widgetInit() {
	register_widget('webclap_widget');
}
add_action('widgets_init', 'webclap_widgetInit');
?>

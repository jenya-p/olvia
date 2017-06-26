<!DOCTYPE html>
<html>
<?
$scripts [] = '/site/js/article.js';
$styles [] = '/site/css/video.css';

include "inc/head.inc"?>

<body class="page-video">
	<div class="lay-wrapper">
		<? include "inc/header.inc" ?>
		
		<section class="main">
			<div class="lay-wrapper-inner">
				<ul class="breadcrumb">
					<li><a href="/home.php">Главная</a></li>
					<li><a href="/video.php">Видео</a></li>
					<li>Причины депрессии и методы ее лечения</li>
				</ul>
				<h1>Причины депрессии и методы ее лечения</h1>
				
				<div class="video-container">
					<iframe allowfullscreen="" frameborder="0" height="800" src="https://www.youtube.com/embed/HhiB34jadXM" width="1180"></iframe></p>
				</div>
				
				<div class="content">
					<h2>Содержание ролика:</h2>
					<ul><li><span>Депрессия как отсутствие желания по тем или иным причинам. </span></li>
					<li><span>Потеряно желание жить. Хочет, но не может получить, отчаяние. </span></li>
					<li><span>Помощь психолога при депрессивном состоянии. </span></li>
					<li><span>В чем отличие депрессии от депрессивного состояния. </span></li>
					<li><span>Потеря энергии, отсутствие желания. </span></li>
					<li><span>Нахождение жизненных ориентиров. Как получить энергию, ресурсы. </span></li>
					<li><span>Улучшение качества жизни.</span></li>
					</ul>
				</div>

				<div class="after-article">
				
					<script type="text/javascript">(function() {
					  if (window.pluso)if (typeof window.pluso.start == "function") return;
					  if (window.ifpluso==undefined) { window.ifpluso = 1;
					    var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
					    s.type = 'text/javascript'; s.charset='UTF-8'; s.async = true;
					    s.src = ('https:' == window.location.protocol ? 'https' : 'http')  + '://share.pluso.ru/pluso-like.js';
					    var h=d[g]('body')[0];
					    h.appendChild(s);
					  }})();</script>
					<div class="pluso" data-background="#ebebeb" data-options="small,square,line,horizontal,counter,theme=01" data-services="vkontakte,odnoklassniki,facebook,twitter,google,moimir,email,print"></div>
				
					<span class="article-author"><label>Автор: </label><a href="/master/5">Юрий Карпенков</a></span>
					<span class="article-date"><i class="icon-calendar"></i>20 мая 2016</span>
				
				</div>

			</div>
		</section>

		<div class="lay-footer-placeholder"></div>
	</div>

	<? include "inc/footer.inc" ?>

</body>
</html>


<?

function video($img, $title){ ?>
	<div class="video-entry">
		<a href="/video.php">
			<img src="/images/video-thumb-<?= $img ?>.jpg" />
			<span class="time"><?= rand(11, 50)?>:<?= rand(0, 5)?>0</span>
			<span><?= $title ?></span>
		</a>
	</div>
<? } ?>
<?php
include __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\Slim();
error_reporting(E_ALL);
ini_set('display_errors',1);

$app->config('debug', true);

$app->get('/', function () use ($app) {
    echo '/:url(/:width)';
});

$app->get('/:url(/:width)', function ($url, $width) use ($app) {
    $imageBlob = file_get_contents('pics/'.$url.'.jpg');
    $imagick = new Imagick();
    $imagick->readImageBlob($imageBlob);
    $gaussRadius = floatval($_GET['gauss_radius'] ?? 0);
    $sigma = floatval($_GET['sigma'] ?? 0.5);
    $threshold = floatval($_GET['threshold'] ?? 0.05);
    $sharp = isset($_GET['sharpen']);
    echo '<html><head>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />';
    echo '</head><body>';
    if ($sharp) {
	    echo '<h2>С резкостью</h2>';
    }
    $filters = [
        'scale'         => 'scale',
        'CUBIC'         => Imagick::FILTER_CUBIC,
        'ROBIDOUX'      => Imagick::FILTER_ROBIDOUX,
        'ROBIDOUXSHARP' => Imagick::FILTER_ROBIDOUXSHARP,
        'LANCZOS'       => Imagick::FILTER_LANCZOS,
        'LANCZOSSHARP'  => Imagick::FILTER_LANCZOSSHARP,
        'LANCZOS2'      => Imagick::FILTER_LANCZOS2,
        'LANCZOS2SHARP' => Imagick::FILTER_LANCZOS2SHARP,
	/*'BOX'           => Imagick::FILTER_BOX,*/
	/* 'TRIANGLE'      => Imagick::FILTER_TRIANGLE,*/
        'HERMITE'       => Imagick::FILTER_HERMITE,
        'HANNING'       => Imagick::FILTER_HANNING,
        'HAMMING'       => Imagick::FILTER_HAMMING,
        'BLACKMAN'      => Imagick::FILTER_BLACKMAN,
        'GAUSSIAN'      => Imagick::FILTER_GAUSSIAN,
        'CATROM'        => Imagick::FILTER_CATROM,
        'MITCHELL'      => Imagick::FILTER_MITCHELL,
        'BESSEL'        => Imagick::FILTER_BESSEL,
        'KAISER'        => Imagick::FILTER_KAISER,
        'PARZEN'        => Imagick::FILTER_PARZEN,
        'WELSH'         => Imagick::FILTER_WELSH,
	'SINC'          => Imagick::FILTER_SINC,
    ];

    $n = 20;    
    
    echo 'Original: <div style="clear:both: margin-bottom:3rem; width: '.$width.'; height:'.$width.'px;"><img style="width:'.$width.'px; height:'.$width.'px" src="/pics/'.$url.'.jpg"/></div>';
    echo '<div style="clear:both;"> <form action="">'
    . '     <input type="submit" name="sharpen" value="Резкость"/><br/>
                Гаусс-радиус<input type="text" name="gauss_radius" value="'.$gaussRadius.'" /><br/>
                Сигма<input type="text" name="sigma" value="'.$sigma.'" /><br/>
                Порог<input type="text" name="threshold" value="'.$threshold.'" /><br/>'
    . '     <input type="submit" name="normal" value="Без резкости"/><br/>';
    echo '</form></div>';    
    echo '<div style="overflow-x:scroll;overflow-y:hidden;width:'.$width.'px">';
    echo '<div style="display:block; white-space:nowrap;">';
    foreach ($filters as $name => $filter) {
        $t1 = microtime(true);
        for ($i = 0; $i<$n; $i++) {
            $im = clone $imagick;
            switch ($filter) {
               case 'scale': $im->scaleImage($width, 0); break;
               default: 
                $im->resizeImage($width, 0, $filter, 1); 
            }
            if ($sharp) {
                $im->unsharpMaskImage($gaussRadius, $sigma, 1, $threshold);
            }
        }
        $t2 = microtime(true);
        $spent = round(($t2 - $t1)/$n*1000, 1);
        echo '<div style="display: inline-block; margin:0 5px 0 5px">'.$name.' ('.$spent.')<br/><img src="data:image/' . $imagick->getImageFormat() . ';base64,' . base64_encode($im->getImageBlob()) . '"/></div>';
        $im->removeImage();
    }
    echo '</div>';
    echo '</div>';
    echo '</body>';
});

$app->run();

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
    if (isset($_GET['krat'])) {
        $width = $imagick->getImageWidth()/2;
        echo '<h3>Кратное:'.$width.'</h3>';
    }
    $gaussRadius = floatval($_GET['gauss_radius'] ?? 0);
    $sigma = floatval($_GET['sigma'] ?? 0.5);
    $threshold = floatval($_GET['threshold'] ?? 0.05);
    $sharp = isset($_GET['sharpen']);
    $quality = intval($_GET['qual'] ?? 100);
    $blur = floatval($_GET['blur'] ?? 1.0);
    echo '<html><head>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />';
    echo '</head><body>';
    if ($sharp) {
	    echo '<h2>С резкостью</h2>';
    }
    $filters = [
        'scale'         => 'scale',
        'LANCZOS'       => Imagick::FILTER_LANCZOS,
        'LANCZOSSHARP'  => Imagick::FILTER_LANCZOSSHARP,
        'ROBIDOUX'      => Imagick::FILTER_ROBIDOUX,
        'ROBIDOUXSHARP' => Imagick::FILTER_ROBIDOUXSHARP,
        'LANCZOS2'      => Imagick::FILTER_LANCZOS2,
        'LANCZOS2SHARP' => Imagick::FILTER_LANCZOS2SHARP,
	/*'BOX'           => Imagick::FILTER_BOX,*/
	/* 'TRIANGLE'      => Imagick::FILTER_TRIANGLE,*/
        'HERMITE'       => Imagick::FILTER_HERMITE,
    /*    'HANNING'       => Imagick::FILTER_HANNING,
        'HAMMING'       => Imagick::FILTER_HAMMING,*/
        'BLACKMAN'      => Imagick::FILTER_BLACKMAN,
        'GAUSSIAN'      => Imagick::FILTER_GAUSSIAN,
        'CATROM'        => Imagick::FILTER_CATROM,
        'MITCHELL'      => Imagick::FILTER_MITCHELL,
        'BESSEL'        => Imagick::FILTER_BESSEL,
/*        'KAISER'        => Imagick::FILTER_KAISER,
        'PARZEN'        => Imagick::FILTER_PARZEN,
        'WELSH'         => Imagick::FILTER_WELSH,*/
        'SINC'          => Imagick::FILTER_SINC,
    ];
    echo '<div style="clear:both;"> <form action="">
    <div style="margin-bottom:10px">
         <button type="submit" name="sharpen" value="1" style="margin-right:10px">Sharpen</button>
            <button type="submit" name="normal" value="1">Without sharpness</button>
            <input type="checkbox" name="krat" value="1" '.(isset($_GET['krat']) ? 'checkbox' : '').'>x/2
    <!--        <input type="checkbox" name="srgb" value="1" '.(isset($_GET['srgb']) ? 'checked' : '').'>sRGB<br/> -->
    </div>
    <div>
    G<input type="text" name="gauss_radius" value="'.$gaussRadius.'" style="width:40px"/>
    S<input type="text" name="sigma" value="'.$sigma.'" style="width:40px"/>
    T<input type="text" name="threshold" value="'.$threshold.'" style="width:40px"/>
    Blur<input type="text" name="blur"  style="width:40px" value="'.$blur.'">
    Q-ty<input type="text" name="qual"  style="width:40px" value="'.$quality.'"></div>';
    echo '</form></div>';    
    echo 'Original: <div style="clear:both: margin-bottom:3rem; width: '.$width.'; height:'.$width.'px;"><img style="width:'.$width.'px; height:'.$width.'px" src="/pics/'.$url.'.jpg"/></div>';
    echo '<div style="overflow-x:scroll;overflow-y:hidden;width:'.$width.'px">';
    echo '<div style="display:block; white-space:nowrap;">';
    foreach ($filters as $name => $filter) {
        $im = clone $imagick;
        if (isset($_GET['srgb'])) {
             $im->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        $im->setImageCompressionQuality($quality);
        switch ($filter) {
           case 'scale': $im->scaleImage($width, 0); break;
           default: 
           $im->resizeImage($width, 0, $filter, $blur); 
        }
        if ($sharp) {
            $im->unsharpMaskImage($gaussRadius, $sigma, 1, $threshold);
        }
        echo '<div style="display: inline-block; margin:0 5px 0 5px">'
            .$name.' <br/><img src="data:image/' . $imagick->getImageFormat() . ';base64,' . base64_encode($im->getImageBlob()) . '"/></div>';
        $im->removeImage();
    }
    echo '</div>';
    echo '</div>';
    echo '</body>';
});

$app->run();

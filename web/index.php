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
//    $imagick->setResolution(200,200);
    $imagick->readImageBlob($imageBlob);
    $gaussRadius = floatval($_GET['gauss_radius'] ?? 0);
    $sigma = floatval($_GET['sigma'] ?? 0.5);
    $threshold = floatval($_GET['threshold'] ?? 0.05);
    $gain = floatval($_GET['gain'] ?? 1.0);
    $sharp = isset($_GET['sharpen']);
    $quality = intval($_GET['qual'] ?? 100);
    $blur = floatval($_GET['blur'] ?? 1.0);
    $retina = isset($_GET['retina']);
    $format = 'jpg';
    echo '<html><head>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />';
    echo '</head><body>';
    if (isset($_GET['krat']) && $_GET['krat'] == 1) {
       $imageWidth = $imagick->getImageWidth()/2;
       echo '<h3>New images width set to:'.$imageWidth.'</h3>';
    } elseif(isset($_GET['krat']) && $_GET['krat'] == 2) {
       $imageWidth = $width*2;
       echo '<h3>New images width set to:'.$imageWidth.'</h3>'; 
    } else {
          $imageWidth = $width;
    }

    if ($sharp) {
	    echo '<h3>Sharp</h3>';
    }
    $filters = [
       /* 'scale'         => 'scale',*/
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
            <input type="radio" name="krat" value="1" '.(isset($_GET['krat']) && $_GET['krat'] == 1 ? 'checked' : '').'>x/2
            <input type="radio" name="krat" value="2" '.(isset($_GET['krat']) && $_GET['krat'] == 2 ? 'checked' : '').'>x*2
    <!--        <input type="checkbox" name="srgb" value="1" '.(isset($_GET['srgb']) ? 'checked' : '').'>sRGB<br/> -->
    </div>
    <div style="font-size:smaller">
    GaussRad<input type="text" name="gauss_radius" value="'.$gaussRadius.'" style="width:30px"/>
    σ<input type="text" name="sigma" value="'.$sigma.'" style="width:30px"/>
    Gain<input type="text" name="gain" value="'.$gain.'" style="width:30px"/>
    Thr<input type="text" name="threshold" value="'.$threshold.'" style="width:40px"/>
    Blur<input type="text" name="blur"  style="width:30px" value="'.$blur.'">
    Q-ty<input type="text" name="qual"  style="width:30px" value="'.$quality.'"></div>';
    echo '</form></div>';    
    echo 'Original: <div style="clear:both: margin-bottom:3rem; width: '.$width.'; height:'.$width.'px;">'
        . '<img style="width:'.$width.'px; height:'.$width.'px" src="/pics/'.$url.'.jpg"/></div>';
    echo '<div style="overflow-x:scroll;overflow-y:hidden;width:'.$width.'px">';
    echo '<div style="display:block; white-space:nowrap;">';
    foreach ($filters as $name => $filter) {
        $im = clone $imagick;
        $im->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        $im->setImageCompressionQuality($quality);
        switch ($filter) {
           case 'scale': $im->scaleImage($imageWidth, 0); break;
           default: 
           $im->resizeImage($imageWidth, 0, $filter, $blur); 
        }
        if ($sharp) {
            $im->unsharpMaskImage($gaussRadius, $sigma, 1, $threshold);
        }
        echo '<div style="display: inline-block; margin:0 5px 0 5px">'
            .$name.' <br/><img style="width:'.$width.'px; height:'.$width.'px" src="data:image/' . $imagick->getImageFormat() . ';base64,' . base64_encode($im->getImageBlob()) . '"/></div>';
        $im->removeImage();
    }
    echo '</div>';
    echo '</div>';

    $distortFilters = ['Lanczos', 'Lanczos2', 'Jinc', 'Gaussian'];
    echo '<div>';
    echo '<h2>Метод Distort </h2>';
    echo '<div style="clear:both;width:'.$width.'px;height:'.$width.'px;"><img src="/pics/'.$url.'.jpg" style="width:100%;height:100%"/></div>';
    echo '<div style="overflow-x:scroll;overflow-y:hidden;width:'.$width.'px">';
    echo '<div style="display:block; white-space:nowrap;">';
    foreach ($distortFilters as $distortFilter) {
        $sharpPart = ' ';
        if ($sharp) {
            $sharpPart = ' -unsharp '.$gaussRadius.'x'.$sigma.'+'.$gain.'+'.$threshold;
        }
        $outUrl = $url.'_'.$distortFilter.'.'.$format;
        $cmd = 'convert ./pics/'.$url.'.jpg -distort Resize '.$imageWidth.'x'.$imageWidth.' -filter '
            .$distortFilter.$sharpPart.' ./pics/'.$outUrl;
        $res = shell_exec($cmd);
        echo '<div style="display: inline-block; margin:0 5px 0 5px; width:'.$width.'px;height:'.$width.'px">'
            .$distortFilter.'<BR>'. '<img src="/pics/'.$outUrl.'" style="width:100%; height:100%"/></div>';
    }
    echo '</div>';
    echo '</div>';

});

$app->run();

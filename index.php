<?php

/*

  A very tiny PHP gallery intended to be dropped into a web-facing folder full of images.

  See the gallery in action: http://spicausis.lv/gallery-demo/ .

  Extensions supported: .jpg, haven't had a need for anything else yet.

  The gallery needs to have a writable "thumbs" folder (it will attempt to create one).

  It uses imagemagick's convert() for the image conversion. All good hosts have it.

  You may add a style.css file to the folder if you suddenly have a wish to customize the looks.

  Written by Einar Lielmanis, einar@spicausis.lv

*/

function main()
{

    global $images;
    $images = glob('*.jpg') + glob('*.JPG');

    if (! file_exists('thumbs')) {
        mkdir('thumbs') or die('Cannot make thumbs');
    }
    is_writable('thumbs') or die('thumbs not writable');

    dispatch(@$_REQUEST['a']);
}

function dispatch($action)
{
    switch($action) {
    case 'make_preview':
        return make_preview(@$_REQUEST['img']);
    case 'make_thumb':
        return make_thumb(@$_REQUEST['img']);
    case 'preview':
        return print_preview();
    default:
        return print_index();
    }

}


function escapeshellarg_utf8($arg)
{
    $current_locale = setlocale(LC_CTYPE, null);
    $restore = false;
    if (setlocale(LC_CTYPE, 'en_US.UTF-8')) {
        $restore = true;
    }

    $ret = escapeshellarg($arg);

    if ($restore) {
        setlocale(LC_CTYPE, $current_locale);
    }

    return $ret;
}


function make_thumb($image)
{
    assert_good_image($image);

    system(sprintf('convert %s -resize 300x170 %s',
        escapeshellarg_utf8($image),
        escapeshellarg_utf8(thumb_of($image))));

    header('Content-type: image/jpeg');
    readfile(thumb_of($image));
}

function make_preview($image)
{
    assert_good_image($image);

    system(sprintf('convert %s -resize 800x600 %s',
        escapeshellarg_utf8($image),
        escapeshellarg_utf8(preview_of($image))));

    header('Content-type: image/jpeg');
    readfile(preview_of($image));
}


function print_index()
{
    print_html_crap();
    global $images;
    foreach($images as $image) {
        printf('<a href="?a=preview&img=%s"><img src="%s"></a>'
            , $image
            , thumb_url($image)
        );
    }
}


function print_preview()
{
    $image = $_REQUEST['img'];
    assert_good_image($image);

    global $images;
    $index = array_search($image, $images);

    print_html_crap();

    $prevlink = null;
    $nextlink = null;
    if (isset($images[$index - 1])) {
        $prevlink = '?a=preview&img=' . $images[$index - 1];
    }
    if (isset($images[$index + 1])) {
        $nextlink = '?a=preview&img=' . $images[$index + 1];
    }
    printf('<div id="counter">%d/%d</div>'
        , $index + 1
        , sizeof($images)
    );
    echo '<p id="links">';
    if ( ! $prevlink) {
        echo '<a class="inactive" href="#">« prev</a>';
    } else {
        printf('<a href="%s">« prev</a>', $prevlink);
    }
    printf('<a id="prev" href="?">home</a>');
    if ( ! $nextlink) {
        echo '<a href="#" class="inactive">next »</a>';
    } else {
        printf('<a href="%s">next »</a>', $nextlink);
    }
    echo '</p>';

    printf('<a href="%s"><img id="preview" src="%s"></a>'
        , $image
        , preview_url($image)
    );
}

function thumb_url($image)
{
    if (file_exists(thumb_of($image))) {
        return thumb_of($image);
    } else {
        return '?a=make_thumb&img=' . $image;
    }
}

function preview_url($image)
{
    if (file_exists(preview_of($image))) {
        return preview_of($image);
    } else {
        return '?a=make_preview&img=' . $image;
    }
}


function thumb_of($image)
{
    return 'thumbs/thu_' . $image;
}


function preview_of($image)
{
    return 'thumbs/prv_' . $image;
}

function assert_good_image($image)
{
    global $images;
    in_array($image, $images) or die('Bad image');
}

function print_html_crap()
{
    header('Content-type: text/html; charset=utf-8');
    echo '<style>';
    echo <<<STYLE
img {
    margin: 2px 2px 0 0;
    display: block;
    float: left;
}
img#preview {
    float: none;
    margin: 0 auto;
    clear: left;
}
p#links {
    text-align: center;
}
p#links a {
    padding: 5px 30px;
    background: #eee;
    margin: 0 1px;
    color: #33c;
}
p#links a.inactive {
    color: #aaa;
    text-decoration: none;
}
#counter {
    position: absolute;
    right: 10px;
    top: 10px;
    color: #888;
}
STYLE;

    if (file_exists('style.css')) {
        readfile('style.css');
    }

    echo '</style>';
}


main();

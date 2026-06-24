<?php

function gerarEmbedTutorial($link) {
    if (empty($link)) {
        return null;
    }

    // YouTube normal: https://www.youtube.com/watch?v=ID
    if (strpos($link, 'youtube.com/watch?v=') !== false) {
        $partes = parse_url($link);
        parse_str($partes['query'], $query);

        if (isset($query['v'])) {
            return 'https://www.youtube.com/embed/' . $query['v'];
        }
    }

    // YouTube curto: https://youtu.be/ID
    if (strpos($link, 'youtu.be/') !== false) {
        $partes = explode('youtu.be/', $link);
        return 'https://www.youtube.com/embed/' . $partes[1];
    }

    // Se já vier no formato embed
    if (strpos($link, 'youtube.com/embed/') !== false) {
        return $link;
    }

    return null;
}
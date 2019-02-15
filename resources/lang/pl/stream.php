<?php

return [

    // verbs
    'verbs' => [
        'create' => 'dodał',
        'delete' => 'usunał',
        'restore' => 'przywrócił',
        'update' => 'zmienił',
        'vote' => 'zagłosował',
        'accept' => 'zaakceptował',
        'reject' => 'zrezygnował z akceptacji',
        'move' => 'przeniósł',
        'rollback' => 'przywrócił',
        'lock' => 'zablokował',
        'unlock' => 'odblokował',
        'copy' => 'skopiował',
        'login' => 'zalogował się',
        'logout' => 'wylogował się',
        'unlink' => 'usunął kopie',
        'merge' => 'połączył',
        'confirm' => 'potwierdził'
    ],

    'nouns' => [
        // objects
        'microblog' => 'mikroblog',
        'comment' => 'komentarz',
        'post' => 'post',
        'topic' => 'wątek',
        'job' => 'ogłoszenie',
        'application' => 'aplikację',
        'pastebin' => 'pastebin',
        'wiki' => 'artykuł',
        'flag' => 'raport',
        'firewall' => 'ban',
        'person' => 'profil',
        'block' => 'blok',
        'group' => 'grupa',
    ],

    'headline' => [
        'microblog'         => ':actor :verb wpis na :object',
        'comment'           => ':actor :verb :object w :target',
        'topic'             => ':actor :verb wątek :object',
        'post'              => ':actor :verb :object w :target',
        'accept'            => ':actor :verb :object w :target',
        'topic:move'        => ':actor :verb :object z :source do :target',
        'job'               => ':actor :verb :object o pracę: :offer',
        'application'       => ':actor :verb :object w ogłoszeniu :target',
        'flag'              => ':actor :verb :object',
        'wiki'              => ':actor :verb :object :title',
        'person'            => ':actor :verb :object',
        'person:confirm'    => ':actor potwierdził adres e-mail',
        'firewall'          => ':actor :verb :object',
        'unknown'           => ':actor :verb',
        'unknown:throttle'  => 'Nieudane logowanie na konto :login',
        'unknown:forgot'    => 'Żądanie przywrócenia hasła do konta :email',
        'unknown:reset'     => 'Zresetowanie hasła do konta :email',
        'block'             => ':actor :verb :object',
        'pastebin'          => ':actor :verb :object',
        'group'             => ':actor :verb :object :name'
    ]
];

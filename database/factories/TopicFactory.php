<?php

use Faker\Generator as Faker;

$factory->define(\Coyote\Topic::class, function (Faker $faker) {
    $subject = $faker->text(60);

    return [
        'subject' => $subject,
        'slug' => str_slug($subject, '_'),
        'forum_id' => function () {
            return \Coyote\Forum::inRandomOrder()->first()->id;
        }
    ];
});

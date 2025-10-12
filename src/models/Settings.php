<?php

namespace taherkathiriya\craftpicturetag\models;

use Craft;
use craft\base\Model;

/**
 * SEOClone settings
 */
class Settings extends Model
{
    public $foo = 'defaultFooValue';
    public $bar = 'defaultBarValue';

    public function defineRules(): array
    {
        return [
            [['foo', 'bar'], 'required'],
        ];
    }
}

<?php
namespace app\core\form;

use app\core\Model;

class Form {
    public static function begin($action, $method) {
        echo sprintf('<form action="%s" method="%s">', $action, $method);
        return new Form();
    }

    public static function end() {
        echo '</form>';
    }

    public function input(Model $model, $attribute) {
        return new InputField($model, $attribute);
    }

    public function textArea(Model $model, $attribute) {
        return new TextareaField($model, $attribute);
    }
}
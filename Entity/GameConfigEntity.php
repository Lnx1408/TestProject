<?php
namespace Entity;

class GameConfigEntity
{
    public string $Language;
    public string $Context;
    public int $Number_items;

    public function __construct($language, $additional_context, $number_items)
    {
        $this->Language = $language;
        $this->Context = $additional_context;
        $this->Number_items = $number_items;
    }

    public function convertirAJson(): string {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

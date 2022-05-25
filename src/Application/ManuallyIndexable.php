<?php

namespace JeroenG\Explorer\Application;

interface ManuallyIndexable
{
    public function mapIndexableData(array $attributes);

    public function getIndexableData(): array;
}

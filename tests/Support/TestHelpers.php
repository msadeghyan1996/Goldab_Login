<?php

function validNationalId(): string
{
    return fake('fa_IR')->nationalCode();
}

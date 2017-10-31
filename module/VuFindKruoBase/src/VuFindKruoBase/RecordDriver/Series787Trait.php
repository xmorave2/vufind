<?php

namespace VuFindKruoBase\RecordDriver;

trait Series787Trait
{
    public function getSerie()
    {
        $ret = [];

        $fields = $this->getMarcRecord()->getFields('787');
        foreach ($fields as $field) {
            if ($subfield_i = $field->getSubfield('i')) {
                if (strpos($subfield_i->getData(), 'Z cyklu') !== false) {
                    $subfield_g = $field->getSubfield('g');
                    $subfield_t = $field->getSubfield('t');
                    $ret[] = [
                        "title" => $subfield_t ?  $subfield_t->getData() : false,
                        "part" => $subfield_g ?  $subfield_g->getData() : false,
                    ];
                }
            }
        }
        return $ret;
    }
}

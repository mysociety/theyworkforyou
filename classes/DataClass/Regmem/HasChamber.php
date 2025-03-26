<?php

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

trait HasChamber {
    public function officialUrl(): string {
        switch ($this->chamber) {
            case 'house-of-commons':
                return "https://www.parliament.uk/mps-lords-and-offices/standards-and-financial-interests/parliamentary-commissioner-for-standards/registers-of-interests/register-of-members-financial-interests/";
            case 'scottish-parliament':
                return "https://www.parliament.scot/msps/register-of-interests";
            case 'northern-ireland-assembly':
                return "https://www.niassembly.gov.uk/your-mlas/register-of-interests/";
            case 'welsh-parliament':
                return "https://senedd.wales/senedd-business/register-of-members-interests/";
            default:
                return '';
        }
    }

    public function displayChamber(): string {
        switch ($this->chamber) {
            case 'house-of-commons':
                return 'House of Commons';
            case 'welsh-parliament':
                return 'Senedd';
            case 'scottish-parliament':
                return 'Scottish Parliament';
            case 'northern-ireland-assembly':
                return 'Northern Ireland Assembly';
            default:
                return 'Unknown Chamber';
        }
    }

}

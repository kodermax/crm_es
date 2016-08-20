<?php
/**
 * Created by PhpStorm.
 * User: MKarpychev
 * Date: 10.12.2015
 * Time: 11:29
 */

namespace tests\Http\Controllers\Es\Crm;


use League\Flysystem\Exception;

class CrmControllerTest extends \TestCase
{
    /**
     * Testing search by phone
     */
    public function testSearchByPhone()
    {
        $this->post('/es/crm/searchByPhone', [
            'phone' => '79037767523',
            'id' => 1698530
        ])->assertResponseStatus(200);

        $this->post('/es/crm/searchByPhone', [
            'phone' => '179037767523',
            'id' => 1698530
        ])->assertResponseStatus(404);

    }

    /**
     * Testing search contacts
     */
    public function testSearchContacts()
    {
        $this->post('/es/crm/contacts', [
            'MODE' => 'SEARCH',
            'VALUE' => 'Иван'
        ])->assertResponseStatus(200);
    }
}
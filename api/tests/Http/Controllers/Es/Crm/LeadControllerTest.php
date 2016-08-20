<?php
/**
 * Created by PhpStorm.
 * User: MKarpychev
 * Date: 11.12.2015
 * Time: 10:13
 */

namespace tests\Http\Controllers\Es\Crm;


class LeadControllerTest extends \TestCase
{
    public function testGet()
    {
        $this->get('/es/crm/lead/1038323')
            ->assertResponseStatus(200);
        $this->get('/es/crm/lead/99999999999')
            ->assertResponseStatus(404);
    }

    public function testSearchDuplicate()
    {

        $this->post('/es/crm/lead/searchDuplicate',
            [
                'id' => 1,
                'landCode' => 'magistr',
                'phone' => '79268780110'
            ])->assertResponseStatus(200);
        $this->post('/es/crm/lead/searchDuplicate',
            [
                'id' => 1,
                'landCode' => 'magistr',
                'phone' => ''
            ])->assertResponseStatus(400);
        $this->post('/es/crm/lead/searchDuplicate',
            [
                'id' => 1,
                'landCode' => '',
                'phone' => ''
            ])->assertResponseStatus(400);
        $this->post('/es/crm/lead/searchDuplicate',
            [
                'id' => '',
                'landCode' => '',
                'phone' => ''
            ])->assertResponseStatus(400);
    }
}
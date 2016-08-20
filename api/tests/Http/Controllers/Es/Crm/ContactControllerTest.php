<?php
/**
 * Created by PhpStorm.
 * User: MKarpychev
 * Date: 10.12.2015
 * Time: 15:47
 */

namespace tests\Http\Controllers\Es\Crm;


class ContactControllerTest extends \TestCase
{
    /**
     * Testing search by phone
     */
    public function testSearchByPhone()
    {
        $this->get('es/crm/contact/phone/79253480682')->assertResponseStatus(200);
        $this->get('es/crm/contact/phone/79127767523')->assertResponseStatus(404);
    }

    public function testSearchByEmail(){
        $this->get('es/crm/contact/mail/maksim.karpychev@draeger.com')->assertResponseStatus(200);
        $this->get('es/crm/contact/mail/admin@admin.com')->assertResponseStatus(404);
    }
}
<?php

class LeadCreateTest extends TestCase
{
    /**
     * Test create lead
     *
     * @return void
     */
    public function testCreateLead()
    {
        $this->post('/crm/leads', [
            'title' => 'Тестовый лид'
        ])->assertResponseStatus(201);

        $this->post('/crm/leads', [
            'title' => ''
        ])->assertResponseStatus(200);
        ob_end_clean();
    }
}

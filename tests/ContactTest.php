<?php

namespace Moneybird;

require_once dirname(__FILE__) . '/../ApiConnector.php';

/**
 * Test class for Contact.
 * Generated by PHPUnit on 2012-07-15 at 12:55:54.
 */
class ContactTest extends \PHPUnit_Framework_TestCase {
	
	protected static $customerId;
	protected static $contactId;
	protected $testContactId;

	/**
	 * @var Contact
	 */
	protected $object;
	
	/**
	 * @var Contact_Service
	 */
	protected $service;
	
	/**
	 * @var ApiConnector
	 */
	protected $apiConnector;
	
	public static function setUpBeforeClass() {
        self::$customerId = 'Cust-'.time();
		self::$contactId = null;
    }

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		include ('config.php');
		
		$transport = getTransport($config);	
		$mapper = new XmlMapper();
		$this->apiConnector = new ApiConnector($config['clientname'], $transport, $mapper);
		$this->service = $this->apiConnector->getService('Contact');
		if (!is_null(self::$contactId)) {
			$this->object = $this->service->getById(self::$contactId);
		}
		$this->testContactId = $config['testcontact'];
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}

	/**
	 * @covers Moneybird\Contact::save
	 */
	public function testSave() {
		$contact = new Contact(array(
			'address1' => 'Address line 1', 
			'address2' => 'Address line 2', 
			'attention' => 'Attention', 
			'bankAccount' => 'Bank account', 
			'chamberOfCommerce' => '1234567', 
			'city' => 'City name', 
			'companyName' => 'My Test & < > \' \ company',
			'contactName' => 'Contact name', 
			'country' => 'Country name', 
			'customerId' => self::$customerId, 
			'email' => 'email@fake.fake', 
			'firstname' => 'John',
			'lastname' => 'Doe', 
			'phone' => '073-1234567', 
			'sendMethod' => 'email', 
			'taxNumber' => '12345678B01', 
			'zipcode' => '1111 AA',
		));
		$contact->save($this->service);
		$this->assertInstanceOf('Moneybird\Contact', $contact);
		self::$contactId = $contact->id;
		$this->assertNotNull(self::$contactId);
		$this->assertGreaterThan(0, self::$contactId);
		$this->assertEquals('My Test & < > \' \ company', $contact->companyName);
	}
	
	/**
	 * @covers Moneybird\Contact_Service::getById
	 */
	public function testGetById() {
		$this->object = $this->service->getById(self::$contactId);
		$this->assertInstanceOf('Moneybird\Contact', $this->object);
		$this->assertEquals($this->object->customerId, self::$customerId);
	}

	/**
	 * @covers Moneybird\Contact_Service::getSyncList
	 */
	public function testGetSyncList() {
		$revision = $this->object->revision;
		$this->assertNotNull($revision, 'Contact '.self::$contactId.' not in synclist');
		$this->object = $this->service->getById(self::$contactId);
		$this->object->setData(array(
			'firstname' => 'Test'.time()
		));
		$this->object->save($this->service);
		sleep(1);
		
		$newRevision = null;
		$syncList = $this->service->getSyncList();
		$this->assertInstanceOf('Moneybird\Contact_Array', $syncList);
		foreach ($syncList as $sync) {
			if ($sync->id == self::$contactId) {
				$newRevision = $sync->revision;
			}
		}
		$this->assertNotNull($newRevision, 'Contact '.self::$contactId.' not in synclist');
		$this->assertGreaterThan($revision, $newRevision);
	}

	/**
	 * @covers Moneybird\Contact_Service::getByIds
	 */
	public function testGetByIds() {
		$contacts = $this->service->getByIds(array(self::$contactId, $this->testContactId));
		$this->assertInstanceOf('Moneybird\Contact_Array', $contacts);
		$this->assertCount(2, $contacts);
	}

	/**
	 * @covers Moneybird\Contact_Service::getAll
	 */
	public function testGetAll() {
		$contacts = $this->service->getAll();
		$this->assertInstanceOf('Moneybird\Contact_Array', $contacts);
		$this->assertGreaterThan(0, count($contacts), 'No contacts found');
	}

	/**
	 * @covers Moneybird\Contact_Service::getByCustomerId
	 */
	public function testGetByCustomerId() {
		$contact = $this->service->getByCustomerId(self::$customerId);
		$this->assertInstanceOf('Moneybird\Contact', $contact);
		$this->assertEquals($contact->id, self::$contactId);
	}

	/**
	 * @covers Moneybird\Contact::createInvoice
	 */
	public function testCreateInvoice() {
		$details = new Invoice_Detail_Array();
		$details->append(new Invoice_Detail(array(
			'amount' => 5, 
			'description' => 'My invoice line',
			'price' => 20,
			'tax' => 0.19,
		)));
		$details->append(new Invoice_Detail(array(
			'amount' => 1, 
			'description' => 'My second invoice line',
			'price' => 12,
			'tax' => 0.19,
		)));
		
		$invoice = $this->object->createInvoice(array(
			'details' => $details,
		));
		$invoice->save($this->apiConnector->getService('Invoice'));
	}

	/**
	 * @covers Moneybird\Contact::getInvoices
	 */
	public function testGetInvoices() {
		$invoices = $this->object->getInvoices($this->apiConnector->getService('Invoice'));
		$this->assertGreaterThan(0, count($invoices), 'No invoices found');
		foreach ($invoices as $invoice) {
			$this->assertEquals(self::$contactId, $invoice->contactId);
			$invoice->delete($this->apiConnector->getService('Invoice'));
		}
	}

	/**
	 * @covers Moneybird\Contact::createRecurringTemplate
	 */
	public function testCreateRecurringTemplate() {
		$details = new RecurringTemplate_Detail_Array();
		$details->append(new RecurringTemplate_Detail(array(
			'amount' => 5, 
			'description' => 'My invoice line',
			'price' => 20,
			'tax' => 0.19,
		)));
		$details->append(new RecurringTemplate_Detail(array(
			'amount' => 1, 
			'description' => 'My second invoice line',
			'price' => 12,
			'tax' => 0.19,
		)));
		
		$template = $this->object->createRecurringTemplate(array(
			'details' => $details,
			'frequencyType' => RecurringTemplate::FREQUENCY_YEAR,
		));
		$template->save($this->apiConnector->getService('RecurringTemplate'));
	}

	/**
	 * @covers Moneybird\Contact::getRecurringTemplates
	 */
	public function testGetRecurringTemplates() {
		$templates = $this->object->getRecurringTemplates($this->apiConnector->getService('RecurringTemplate'));
		$this->assertGreaterThan(0, count($templates), 'No templates found');
		foreach ($templates as $template) {
			$this->assertEquals(self::$contactId, $template->contactId);
			$template->delete($this->apiConnector->getService('RecurringTemplate'));
		}
	}

	/**
	 * @covers Moneybird\Contact::createEstimate
	 */
	public function testCreateEstimate() {
		$details = new Estimate_Detail_Array();
		$details->append(new Estimate_Detail(array(
			'amount' => 5, 
			'description' => 'My invoice line',
			'price' => 20,
			'tax' => 0.19,
		)));
		$details->append(new Estimate_Detail(array(
			'amount' => 1, 
			'description' => 'My second invoice line',
			'price' => 12,
			'tax' => 0.19,
		)));
		
		$template = $this->object->createEstimate(array(
			'details' => $details,
		));
		$template->save($this->apiConnector->getService('Estimate'));
	}

	/**
	 * @covers Moneybird\Contact::getEstimates
	 */
	public function testGetEstimates() {
		$estimates = $this->object->getEstimates($this->apiConnector->getService('Estimate'));
		$this->assertGreaterThan(0, count($estimates), 'No estimates found');
		foreach ($estimates as $estimate) {
			$this->assertEquals(self::$contactId, $estimate->contactId);
			$estimate->delete($this->apiConnector->getService('Estimate'));
		}
	}

	/**
	 * @covers Moneybird\Contact::createIncomingInvoice
	 */
	public function testCreateIncomingInvoice() {
		$details = new IncomingInvoice_Detail_Array();
		$details->append(new IncomingInvoice_Detail(array(
			'amount' => 5, 
			'description' => 'My invoice line',
			'price' => 20,
			'tax' => 0.19,
		)));
		$details->append(new IncomingInvoice_Detail(array(
			'amount' => 1, 
			'description' => 'My second invoice line',
			'price' => 12,
			'tax' => 0.19,
		)));
		
		$template = $this->object->createIncomingInvoice(array(
			'invoiceId' => '2012-'.time(),
			'invoiceDate' => new \DateTime(),
			'details' => $details,
			'currency' => 'EUR',
		));
		$template->save($this->apiConnector->getService('IncomingInvoice'));
	}

	/**
	 * @covers Moneybird\Contact::getIncomingInvoices
	 */
	public function testGetIncomingInvoices() {
		$invoices = $this->object->getIncomingInvoices($this->apiConnector->getService('IncomingInvoice'));
		$this->assertGreaterThan(0, count($invoices), 'No invoices found');
		foreach ($invoices as $invoice) {
			$this->assertEquals(self::$contactId, $invoice->contactId);
			$invoice->delete($this->apiConnector->getService('IncomingInvoice'));
		}
	}
	
	/**
	 * @covers Moneybird\Contact::delete
	 */
	public function testDelete() {
		$this->object->delete($this->service);
		
		$this->setExpectedException('Moneybird\NotFoundException');
		$this->service->getById(self::$contactId);
	}
	/**/
}

?>

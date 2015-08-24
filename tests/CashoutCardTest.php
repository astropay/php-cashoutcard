include __DIR__ . '/../vendor/autoload.php';

class CashoutCardTest extends PHPUnit_Framework_TestCase {

    function testCreate() {
        $this->assertInstanceOf('Astropay\CashoutCard', new Astropay\CashoutCard());
    }

}

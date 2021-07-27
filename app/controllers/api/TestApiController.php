<?php
class TestApiController extends \devpirates\MVC\Base\ApiController {
    public function Index() {
        $this->respond(json_decode('{ "testProp1": "testVal1", "testProp2": "testVal2",  "testProp3": "testVal3" }'));
    }
}
?>
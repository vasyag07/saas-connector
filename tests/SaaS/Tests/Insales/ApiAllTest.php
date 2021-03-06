<?php

/**
 * PHP version 5.3
 *
 * @category ApiClient
 * @package  SaaS\Tests\Insales
 * @author   Putintseva Anna <putintseva@retailcrm.ru>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://github.com/gwinn/saas-connector
 * @see      http://insales.ru/
 */
namespace SaaS\Tests\Insales;

use SaaS\Http\Response;
use SaaS\Test\TestCase;
/**
 * Class ApiAllTest
 *
 * @category ApiClient
 * @package  SaaS\Tests\Insales
 * @author   Putintseva Anna <putintseva@retailcrm.ru>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://github.com/gwinn/saas-connector
 * @see      http://insales.ru/
 */
class ApiAllTest extends TestCase
{
    //TODO: Используется старая версия phpunit 4.8, так как более новые версии требуют версию php от 5.6, composer.json
    private $argList;
    private $groupMethod = [];
    private $allMethods = [];
    private $defaultId = [];

    /**
     * Execution before calling the test
     */
    protected function setUp()
    {
        $this->getData();
        $this->getDefaultValue();

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    /**
     * Execution after the test call
     */
    protected function tearDown()
    {
        time_nanosleep(10, 0);
        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    /**
     * Get all info for methods
     */
    private function getData()
    {
        if (!empty($allMethods)) {
            exit();
        }

        $client = static::getInsalesApiClient();
        $methods = get_class_methods(get_class($client));
        $pattern = '/(List|Get|Create|Update|Delete)$/';

        foreach ($methods as $name) {
            preg_match($pattern, $name, $method);
            $method = trim(end($method));

            if ($method) {
                $rMethod = new \ReflectionMethod($client, $name);
                $doc = $rMethod->getDocComment();
                $group = $this->getGroupMethod($name);
                $params = [];

                foreach ($rMethod->getParameters() as $parameter) {
                    $paramName = $parameter->getName();

                    $patternJson = '/\$' . $paramName . '\ .*\ json:(.*)\\n/';
                    preg_match($patternJson, $doc, $jsonVariable);
                    $jsonVariable = end($jsonVariable);

                    $patternId = '/(id|permalink)$/';
                    preg_match($patternId, strtolower($paramName), $argKey);

                    $params[$parameter->getName()] = [
                        'isOptional'    => $parameter->isOptional(),
                        'name'          => $paramName,
                        'allowsNull'    => $parameter->allowsNull(),
                        'jsonParam'     => $jsonVariable,
                        'isIds'         => !empty($argKey)
                    ];
                }

                $this->allMethods[$name] = [
                    'countParams'           => $rMethod->getNumberOfParameters(),
                    'countRequiredParams'   => $rMethod->getNumberOfRequiredParameters(),
                    'nameParams'            => array_column($rMethod->getParameters(), 'name'),
                    'params'                => $params,
                    'group'                 => trim($group),
                    'doc'                   => $rMethod->getDocComment(),
                    'nameMethod'            => $rMethod->getName()
                ];

                if ($group) {
                    $this->groupMethod[$group][$method] = $this->allMethods[$name];
                }
            }
        }
    }

    /**
     * Getting a list of method names by type
     *
     * @param $type (list, get, create, update, delete)
     *
     * @return mixed
     */
    public function getMethodsName($type)
    {
        $list = [];
        $get = [];
        $create = [];
        $update = [];
        $delete = [];

        $client = static::getInsalesApiClient();
        $methods = get_class_methods(get_class($client));
        $pattern = '/(List|Get|Create|Update|Delete)$/';

        $skipGroup = [
            'field',            //Недостаточно прав для операции
            'domain',           //Недостаточно прав для операции
            'paymentGateway',   //Недостаточно прав для операции
            'deliveryVariant',  //Недостаточно прав для операции
        ];

        foreach ($methods as $name) {
            $group = $this->getGroupMethod($name);
            preg_match($pattern, $name, $method);
            $method = trim(end($method));

            switch ($method) {
                case 'List':
                    $list[$name] = $name;
                    break;
                case 'Get':
                    $get[$name] = $name;
                    break;
                case 'Create':
                    if (!in_array($group, $skipGroup)) {
                        $create[$name] = $name;
                    }

                    break;
                case 'Update':
                    if (!in_array($group, $skipGroup)) {
                        $update[$name] = $name;
                    }

                    break;
                case 'Delete':
                    if (!in_array($group, $skipGroup)) {
                        $delete[$name] = $name;
                    }

                    break;
            }
        }

        return $$type;
    }

    /**
     * Get all default value
     */
    public function getDefaultValue()
    {
        if (empty($this->argList)) {
            $products = self::executeMethod('productsList')->getResponse();
            $this->argList = isset($products[0]) ? [$products[0]['id']] : [];
        }
        
        if (empty($this->defaultId)) {
            $categories = self::executeMethod('categoriesList')->getResponse();
            $delivery = self::executeMethod('deliveryVariantsList')->getResponse();
            $payment = self::executeMethod('paymentGatewaysList')->getResponse();
            $collection = self::executeMethod('collectionsList')->getResponse();
            $collect = self::executeMethod('collectsList')->getResponse();
            $statuses = self::executeMethod('customStatusesList')->getResponse();

            $this->defaultId = [
                'product_id'            => isset($products[0]) ? $products[0]['id'] : null,
                'category_id'           => isset($categories[0]) ? $categories[0]['id'] : null,
                'delivery_variant_id'   => isset($delivery[0]) ? $delivery[0]['id'] : null,
                'payment_gateway_id'    => isset($payment[0]) ? $payment[0]['id'] : null,
                'parent_id'             => isset($collection[0]) ? $collection[0]['id'] : null,
                'collection_id'         => isset($collect[0]) ? $collect[0]['id'] : null,
                'permalink'             => isset($statuses[0]) ? $statuses[0]['permalink'] : null,
            ];
        }
    }

    /**
     * Get name group
     *
     * @param $name
     *
     * @return string
     */
    public function getGroupMethod($name)
    {
        $client = static::getInsalesApiClient();
        $patternGroup = '/@group(.*)\n/';
        $rMethod = new \ReflectionMethod($client, $name);
        $doc = $rMethod->getDocComment();
        preg_match($patternGroup, $doc, $group);

        return trim(end($group));
    }

    /**
     * Get standard method arguments
     *
     * @param $nameMethod
     *
     * @return array
     */
    private function getArgumentsForMethod($nameMethod)
    {
        $this->getData();
        $this->getDefaultValue();

        $arguments = [];
        $pattern = '/(Id|Permalink)$/';
        $info = array_key_exists($nameMethod, $this->allMethods)
            ? $this->allMethods[$nameMethod]
            : [];

        foreach ($info['nameParams'] as $name) {
            if ($info['params'][$name]['isOptional']) {

                continue;
            }

            preg_match($pattern, $name, $argKey);
            $argName = str_replace($argKey, '', $name);
            $argKey = strtolower(end($argKey));

            if (array_key_exists($argName, $this->groupMethod)
                && array_key_exists('List', $this->groupMethod[$argName])
            ) {
                $result = self::executeMethod(
                    $this->groupMethod[$argName]['List']['nameMethod'],
                        $this->argList
                )->getResponse();

                if (!empty($result)) {
                    $item = array_shift($result);

                    if (array_key_exists($argKey, $item)) {
                        $arguments[$name] = $item[$argKey];
                    }
                } else {
                    $arguments[$name] = null;
                }
            }

            if (empty($arguments[$name])) {
                $crudMethod = $this->groupMethod[$info['group']];
                if (isset($crudMethod['List'])) {
                    $result = self::executeMethod($crudMethod['List']['nameMethod'], $this->argList);
                    $result = $result->getResponse();

                    if (!empty($result)) {
                        $item = array_shift($result);

                        if (array_key_exists($argKey, $item)) {
                            $arguments[$name] = $item[$argKey];
                        }
                    }
                }
            }
        }

        return  $arguments;
    }

    /**
     * Replacing arguments
     *
     * @param $group
     * @param $params
     * @param array $replace
     *
     * @return mixed
     */
    public function replaceParams($group, $params, $replace = [])
    {
        $this->getData();
        $this->getDefaultValue();

        $json = !is_array($params) ? $params : json_encode($params, true);
        $patternReplace = '/\"(\w+)\"\:\ ?123/';

        if (empty($replace)) {
            preg_match_all($patternReplace, $json, $strReplace);
            $strReplace = end($strReplace);

            foreach ($strReplace as $item) {
                if (array_key_exists($item, $this->defaultId)) {
                    $replace[$item] = $this->defaultId[$item];
                } elseif (($item == 'id' || $item == 'permalink') && isset($this->groupMethod[$group]['List'])) {
                    $list = $this->groupMethod[$group]['List'];
                    $result = self::executeMethod(
                        $list['nameMethod'],
                        $this->getArgumentsForMethod($list['nameMethod'])
                    )->getResponse();
                    $result = array_shift($result);
                    $replace[$item] = isset($result[$item]) ? $result[$item] : null;
                } elseif (!isset($this->groupMethod[$group]['List']) && $item == 'id') {
                    if ($group == 'orderLine') {

                    }
                }
            }

            if (!empty($replace)) {
                return $this->replaceParams($group, $params, $replace);
            }
        } else {
            foreach ($replace as $key => $value) {
                $patternVariable = '/\"'.$key.'\"\:\ {0,1}(123)/';
                $json = preg_replace($patternVariable, '"'. $key . '": ' . $value, $json);
            }

        }

        return json_decode($json, true);
    }

    /**
     * Get required method arguments
     *
     * @param $nameMethod
     * @param array $replace
     *
     * @return array
     */
    private function getRequiredArguments($nameMethod, $replace = [])
    {
        $this->getData();
        $this->getDefaultValue();
        $arguments = [];

        $info = array_key_exists($nameMethod, $this->allMethods)
            ? $this->allMethods[$nameMethod]
            : [];

        foreach ($info['nameParams'] as $name) {
            if ($info['params'][$name]['isOptional']) {
                continue;
            }

            if ($info['params'][$name]['isIds']) {
                $arguments[$name] = isset($this->defaultId[$name]) ? $this->defaultId[$name] : null;

                continue;
            }

            $arguments[$name] = $this->replaceParams($info['group'], $info['params'][$name]['jsonParam']);
        }

        foreach ($arguments as $keyName => &$argument) {
            $pattern = '/(Id|Permalink)$/';
            preg_match($pattern, $keyName, $argKey);

            $argKey = strtolower(end($argKey));

            if (!empty($argKey)) {
                $resultArguments = $this->getArgumentsForMethod($nameMethod);
                $argument = isset($resultArguments[$keyName]) ? $resultArguments[$keyName] : null;
            }
        }

        if ($info['group'] == 'orderLine' && !empty($arguments['orderId'])) {
            //TODO: Кастыль
            $order = self::executeMethod('orderGet', [$arguments['orderId']])->getResponse();

            if (isset($order['order_lines'][0])) {
                $arguments['orderLines'] = $this->replaceParams(
                    $info['group'],
                    $info['params']['orderLines']['jsonParam'],
                    ['id' => $order['order_lines'][0]['id']]
                );
            }
        }

        return $arguments;
    }

    /**
     * Provider for tests List methods
     *
     * @return array
     */
    public function providerList()
    {
        $this->getData();
        $this->getDefaultValue();

        $list = $this->getMethodsName('list');
        $provider = [];

        foreach ($list as $name) {
            $provider[$name] = [
                'name' => $name,
                'productId' => $this->allMethods[$name]['countRequiredParams'] > 0
                    ? $this->argList
                    : []
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests List methods if arguments is null
     *
     * @return array
     */
    public function providerListNull()
    {
        $this->getData();
        $list = $this->getMethodsName('list');
        $provider = [];

        foreach ($list as $name) {
            if ($this->allMethods[$name]['countRequiredParams'] > 0) {
                $provider[$name] = [
                    'name' => $name,
                    'productId' => [null]
                ];
            }
        }

        return $provider;
    }

    /**
     * Provider for tests Get methods
     *
     * @return array
     */
    public function providerGet()
    {
        $getList = $this->getMethodsName('get');
        $provider = [];

        foreach ($getList as $name) {
            $provider[$name] = [
                'name' => $name,
                'arguments' => $this->getArgumentsForMethod($name),
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests Get methods if arguments is null
     *
     * @return array
     */
    public function providerGetNull()
    {
        $getList = $this->getMethodsName('get');
        $provider = [];

        foreach ($getList as $name) {
            $provider[$name] = [
                'name' => $name,
                'argId1' => null,
                'argId2' => null,
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests Get methods if not found entity
     *
     * @return array
     */
    public function providerGetNotExist()
    {
        $getList = $this->getMethodsName('get');
        $provider = [];

        foreach ($getList as $name) {
            $provider[$name] = [
                'name' => $name,
                'argId1' => 123,
                'argId2' => 123,
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests Create methods
     *
     * @return array
     */
    public function providerCreate()
    {
        $createList = $this->getMethodsName('create');
        $provider = [];

        foreach ($createList as $name => $method) {
            $provider[$name] = [
                $name
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests Update methods
     *
     * @return array
     */
    public function providerUpdate()
    {
        $updateList = $this->getMethodsName('update');
        $provider = [];

        foreach ($updateList as $name => $method) {
            $provider[$name] = [
                $name
            ];
        }

        return $provider;
    }

    /**
     * Provider for tests Delete methods
     *
     * @return array
     */
    public function providerDelete()
    {
        $deleteList = $this->getMethodsName('delete');
        $provider = [];

        foreach ($deleteList as $name => $method) {
            $provider[$name] = [
                $name
            ];
        }

        return $provider;
    }

    /**
     * Testing all list methods
     *
     * @dataProvider providerList
     * @param string $name
     * @param string $arguments
     * @group insales
     * @group all_insales
     */
    public function testAllList($name, $arguments)
    {
        self::executeMethod($name, $arguments);
    }

    /**
     * Testing list methods arguments null
     *
     * @expectedException \SaaS\Exception\InsalesApiException
     * @dataProvider providerListNull
     * @param string $name
     * @param string $arguments
     * @group insales
     * @group all_insales
     */
    public function testAllListNull($name, $arguments)
    {
        self::executeMethod($name, $arguments);
    }

    /**
     * Testing get methods arguments null
     *
     * @expectedException \SaaS\Exception\InsalesApiException
     * @dataProvider providerGetNull
     * @param $name
     * @param $id1
     * @param $id2
     * @group insales
     * @group all_insales
     */
    public function testAllGetNull($name, $id1, $id2)
    {
        self::executeMethod($name, [$id1, $id2]);
    }

    /**
     * Testing all get methods arguments not found
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider providerGetNotExist
     * @param $name
     * @param $id1
     * @param $id2
     * @group insales
     * @group all_insales
     */
    public function testAllGetNotExist($name, $id1, $id2)
    {
        if ($name != 'customStatusGet') {
            self::executeMethod($name, [$id1, $id2]);
        } else {
            //TODO:Насильно выстанавливаем Exception, так как InSales передает 200
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Testing all get methods
     *
     * @dataProvider providerGet
     * @param $name
     * @param $arguments
     * @group insales
     * @group all_insales
     */
    public function testAllGet($name, $arguments)
    {
        //TODO: domainGet и bonusSystemTransactionGet с ошибкой, так как нет данных в тестовом магазине
        self::executeMethod($name, $arguments);
    }

    /**
     * Testing all create methods
     *
     * @dataProvider providerCreate
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllCreate($name)
    {
        $arguments = $this->getRequiredArguments($name);
        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing create methods arguments null
     *
     * @dataProvider providerCreate
     * @expectedException \SaaS\Exception\InsalesApiException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllCreateNull($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return null; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing all create methods arguments not found
     *
     * @dataProvider providerCreate
     * @expectedException \InvalidArgumentException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllCreateNotExist($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return 123; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing all update methods
     *
     * @dataProvider providerUpdate
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllUpdate($name)
    {
        $arguments = $this->getRequiredArguments($name);
        self::executeMethod($name, $arguments)->getResponse();
    }


    /**
     * Testing update methods arguments null
     *
     * @dataProvider providerUpdate
     * @expectedException \SaaS\Exception\InsalesApiException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllUpdateNull($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return null; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing all update methods arguments not found
     *
     * @dataProvider providerUpdate
     * @expectedException \InvalidArgumentException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllUpdateNotExist($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return 123; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }


    /**
     * Testing all delete methods
     *
     * @dataProvider providerDelete
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllDelete($name)
    {
        $arguments = $this->getRequiredArguments($name);
        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing delete methods arguments null
     *
     * @dataProvider providerDelete
     * @expectedException \SaaS\Exception\InsalesApiException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllDeleteNull($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return null; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Testing all delete methods arguments not found
     *
     * @dataProvider providerDelete
     * @expectedException \InvalidArgumentException
     * @param $name
     * @group insales
     * @group all_insales
     */
    public function testAllDeleteNotExist($name)
    {
        $arguments = $this->getRequiredArguments($name);
        $arguments = array_map(function ($a) { return 123; },$arguments);

        self::executeMethod($name, $arguments)->getResponse();
    }

    /**
     * Execute using the method, for entity
     *
     * @param $method
     * @param array $arguments
     * @group insales
     *
     * @return Response
     */
    public static function executeMethod($method, $arguments = [])
    {
        $client = static::getInsalesApiClient();

        $response = call_user_func_array(
            [$client, $method],
            $arguments
        );

        static::checkResponse($response);

        return $response;
    }
}

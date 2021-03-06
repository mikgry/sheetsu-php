<?php
/**
 * Unit test for Sheetsu main class
 * @Author: Emiliano Zublena - https://github.com/emilianozublena
 * @Package: Sheetsu PHP Library - https://github.com/emilianozublena/sheetsu-php
 */

namespace Sheetsu\Tests;


use Sheetsu\Collection;
use Sheetsu\Response;
use Sheetsu\Sheetsu;
use Sheetsu\Model;

class SheetsuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider validGetConfigProvider
     */
    public function testConstructSetsConnectionSheetIdAndSheetUrlFromConfig($config)
    {
        $sheetsu = new Sheetsu([
            'sheetId' => $config['sheetId']
        ]);
        $response = $sheetsu->read();
        $collection = $response->getCollection();
        $this->assertTrue($collection instanceof Collection);
    }

    /**
     * @dataProvider validGetConfigProvider
     */
    public function testReadGetsAResponseObjectAndCollectionObject($config)
    {
        $sheetsu = new Sheetsu([
            'sheetId' => $config['sheetId']
        ]);
        $response = $sheetsu->read($config['limit'], $config['offset']);
        $collection = $response->getCollection();
        $this->assertTrue($response instanceof Response && $collection instanceof Collection);
    }

    /**
     * @dataProvider validGetConfigProvider
     */
    public function testSearchGetsAResponseObjectAndCollectionObject($config)
    {
        $sheetsu = new Sheetsu([
            'sheetId' => $config['sheetId']
        ]);
        $response = $sheetsu->search($config['conditions'], $config['limit'], $config['offset'],
            $config['ignore_case']);
        $model = $response->getModel();
        $this->assertEquals(strtolower($config['conditions']['name']), strtolower($model->name));
    }

    /**
     * @dataProvider validPostConfigProvider
     */
    public function testCreateSuccessfullyCreatesDataInApi($config)
    {
        $sheetsu = new Sheetsu([
            'sheetId' => $config['sheetId']
        ]);
        $response = $sheetsu->create($config['insertData']);
        $collection = $this->getCollectionFromInsertData($config['insertData']);

        $this->assertTrue($response instanceof Response);

        foreach ($collection->getModels() as $model) {
            $getResponse = $sheetsu->search($model->_prepareModelAsArray());
            $this->assertTrue($getResponse instanceof Response && $getResponse->getModel() instanceof Model);
        }
    }

    private function getCollectionFromInsertData($data)
    {
        if (is_array($data)) {
            $collection = new Collection();
            foreach ($data as $value) {
                $collection->add(Model::create($value));
            }
            return $collection;
        } elseif ($data instanceof Model) {
            $collection = new Collection();
            $collection->add($data);
            return $collection;
        } elseif ($data instanceof Collection) {
            return $data;
        }
    }

    /**
     * @dataProvider validPutConfigProvider
     */
    public function testUpdateSuccessfullyUpdatesDataInApi($config)
    {
        $sheetsu = new Sheetsu([
            'sheetId' => $config['sheetId']
        ]);
        $response = $sheetsu->update('name', $config['name'], $config['insertData']);
        $this->assertTrue($response->getHttpStatus() == 200);
    }

    public function testDeleteSuccessfullyDeletesDataInApi()
    {
        $sheetsu = new Sheetsu([
            'sheetId' => 'dc31e735c9ce'
        ]);
        $response = $sheetsu->delete('name', 'Atahualpa');
        $this->assertTrue($response->getHttpStatus() == 204);
    }

    public function testInitializeChangesSheetIdAndSheetUrl()
    {
        $sheetsu = new Sheetsu([
            'sheetId' => null
        ]);
        $response = $sheetsu->initialize(['sheetId' => 'dc31e735c9ce'])->read();
        $this->assertTrue($response instanceof Response && $response->getModel() instanceof Model);
    }

    public function testSheetSetsSpecificSheet()
    {
        $sheetsu = new Sheetsu([
            'sheetId' => 'dc31e735c9ce'
        ]);
        $sheetsu->sheet('sheet2');
        $this->assertEquals($sheetsu->_getSheetUrl(), 'https://sheetsu.com/apis/v1.0/dc31e735c9ce/sheets/sheet2');
    }

    public function testReadFromSpecificSheet()
    {
        $sheetsu = new Sheetsu([
            'sheetId' => 'dc31e735c9ce'
        ]);
        $response = $sheetsu->sheet('sheet2')->read();
        $this->assertTrue($response instanceof Response && $response->getModel() instanceof Model);
    }

    public function testWholeReinitializesLibrary()
    {
        $sheetsu = new Sheetsu([
            'sheetId' => 'dc31e735c9ce'
        ]);
        $sheetsu->sheet('sheet2');
        $response = $sheetsu->whole()->read();
        $this->assertTrue($response instanceof Response && $response->getModel() instanceof Model);
    }

    public function invalidConfigProvider()
    {
        return [
            [
                [
                    'method'  => 'get',
                    'sheetId' => 'asdad12',
                    'limit'   => 0,
                    'offset'  => 0
                ]
            ]
        ];
    }

    public function validGetConfigProvider()
    {
        return [
            [
                [
                    'method'      => 'get',
                    'sheetId'     => 'dc31e735c9ce',
                    'limit'       => 0,
                    'offset'      => 0,
                    'conditions'  => ['name' => 'Peter'],
                    'ignore_case' => false
                ]
            ],
            [
                [
                    'method'      => 'get',
                    'sheetId'     => 'dc31e735c9ce',
                    'limit'       => 1,
                    'offset'      => 0,
                    'conditions'  => ['name' => 'peter'],
                    'ignore_case' => true
                ]
            ],
            [
                [
                    'method'      => 'get',
                    'sheetId'     => 'dc31e735c9ce',
                    'limit'       => 1,
                    'offset'      => 1,
                    'conditions'  => ['name' => 'Peter'],
                    'ignore_case' => false
                ]
            ]
        ];
    }

    public function validPostConfigProvider()
    {
        $collection = new Collection();
        $collection->addMultiple([
            Model::create(['id' => 25, 'name' => 'John', 'score' => 'Baptist']),
            Model::create(['id' => 26, 'name' => 'Atahualpa', 'score' => 'Yupanqui'])
        ]);
        return [
            [
                [
                    'method'     => 'post',
                    'sheetId'    => 'dc31e735c9ce',
                    'insertData' => [
                        ['id' => 25, 'name' => 'John', 'score' => 'Baptist'],
                        ['id' => 26, 'name' => 'Atahualpa', 'score' => 'Yupanqui']
                    ]
                ]
            ],
            [
                [
                    'method'     => 'post',
                    'sheetId'    => 'dc31e735c9ce',
                    'insertData' => new Model(
                        ['id' => 25, 'name' => 'John', 'score' => 'Baptist']
                    )
                ]
            ],
            [
                [
                    'method'     => 'post',
                    'sheetId'    => 'dc31e735c9ce',
                    'insertData' => $collection
                ]
            ]
        ];
    }

    public function validPutConfigProvider()
    {
        return [
            [
                [
                    'method'     => 'put',
                    'sheetId'    => 'dc31e735c9ce',
                    'name'       => 'Atahualpa',
                    'insertData' => Model::create(['name' => 'Tupac'])
                ]
            ],
            [
                [
                    'method'     => 'put',
                    'sheetId'    => 'dc31e735c9ce',
                    'name'       => 'Tupac',
                    'insertData' => ['name' => 'Atahualpa']
                ]
            ]
        ];
    }
}
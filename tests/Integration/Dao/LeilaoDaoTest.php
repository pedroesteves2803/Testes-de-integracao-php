<?php

namespace Alura\Leilao\Tests\Integration\Dao;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Infra\ConnectionCreator;
use Alura\Leilao\Model\Leilao;
use PHPUnit\Framework\TestCase;

class LeilaoDaoTest extends TestCase
{
    private static $pdo;

    public static function setUpBeforeClass(): void
    {
       self::$pdo = new \PDO('sqlite::memory:');

       self::$pdo->exec("CREATE TABLE leiloes (
            id INTEGER PRIMARY KEY, 
            descricao TEXT, 
            finalizado BOOL, 
            dataInicio TEXT
        )");

    }

    protected function setUp(): void
    {

        self::$pdo->beginTransaction();
    }

    /**
     * @dataProvider leiloes
     */
    public function testBuscaLieloesNaoFinalizados(array $leiloes)
    {
        //arrange
        $leilaoDao = new LeilaoDao(self::$pdo);

        foreach($leiloes as $leilao)
        {
            $leilaoDao->salva($leilao);
        }
        
        //act
        $leiloes = $leilaoDao->recuperarNaoFinalizados();

        //assert
        self::assertCount(1, $leiloes);
        self::assertContainsOnlyInstancesOf(Leilao::class, $leiloes);
        self::assertSame('Variant', $leiloes[0]->recuperarDescricao());
        self::assertFalse($leiloes[0]->estaFinalizado());

    }

    /**
     * @dataProvider leiloes
     */
    public function testBuscaLieloesFinalizados(array $leiloes)
    {
        //arrange
        $leilaoDao = new LeilaoDao(self::$pdo);

        foreach($leiloes as $leilao)
        {
            $leilaoDao->salva($leilao);
        }

        //act
        $leiloes = $leilaoDao->recuperarFinalizados();

        //assert
        self::assertCount(1, $leiloes);
        self::assertContainsOnlyInstancesOf(Leilao::class, $leiloes);
        self::assertSame('Fiat', $leiloes[0]->recuperarDescricao());
        self::assertTrue($leiloes[0]->estaFinalizado());

    }

    public function testAoAtulizarLeilaoStatusDeveSerAlterado()
    {
        $leilao = new Leilao('bmw');
        $leilaoDao = new LeilaoDao(self::$pdo);
        $leilao = $leilaoDao->salva($leilao);

        $leiloes = $leilaoDao->recuperarNaoFinalizados();
        self::assertCount(1, $leiloes);
        self::assertSame('bmw', $leiloes[0]->recuperarDescricao());
        self::assertFalse($leiloes[0]->estaFinalizado());

        $leilao->finaliza();
        $leilaoDao->atualiza($leilao);

        $leiloes = $leilaoDao->recuperarFinalizados();
        self::assertCount(1, $leiloes);
        self::assertSame('bmw', $leiloes[0]->recuperarDescricao());
        self::assertTrue($leiloes[0]->estaFinalizado());


    }

    protected function tearDown(): void
    {
        self::$pdo->rollBack();
    }

    public function leiloes()
    {
        $naoFinalizado = new Leilao('Variant');
        $finalizado = new Leilao('Fiat');
        $finalizado->finaliza();


        return [
            [
                [$naoFinalizado, $finalizado]
            ]
        ];
    }
}

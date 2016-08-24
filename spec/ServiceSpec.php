<?php
namespace spec\Ouarea\Sms\Chuanglan;

use GuzzleHttp\RequestOptions;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class ServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Ouarea\Sms\Chuanglan\Service::class, [
            'account',    // account
            'password',   // password
            [
                'affix' => '123',  // affix
            ],
            $client
        ]);
    }

    //=====================================
    //          Send Message
    //=====================================
    function it_throws_exception_if_message_too_long(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信内容过长'))
            ->duringSend('13800138000', str_random(1000));
    }

    function it_throws_exception_if_no_subscribers_given(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信接收用户未指定'))
            ->duringSend('', str_random());
    }

    function it_should_have_message_sent_for_single_subscriber(ClientInterface $client)
    {
        $client->request('POST', 'http://222.73.117.158/msg/HttpBatchSendSM',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['account']    == 'account' &&
                $request['pswd']  == 'password' &&
                $request['mobile'] == '13800138000' &&
                $request['msg'] == '发送消息' &&
                $request['needstatus'] == false &&
                $request['extno'] == '123';
            }))->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/data/single_success.txt')));

        $this->shouldNotThrow()->duringSend('13800138000', '发送消息');
    }

    function it_should_have_message_sent_for_multiple_subscribers(ClientInterface $client)
    {
        $client->request('POST', 'http://222.73.117.158/msg/HttpBatchSendSM',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['account']    == 'account' &&
                $request['pswd']  == 'password' &&
                $request['mobile'] == '13800138000,13800138001' &&
                $request['msg'] == '发送消息' &&
                $request['needstatus'] == false &&
                $request['extno'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));

        $this->shouldNotThrow()->duringSend(['13800138000', '13800138001'], '发送消息');
    }

    function it_should_have_message_sent_for_subscribers_that_exceeds_the_limit(ClientInterface $client)
    {
        $subscribersBatchOne = $this->makeSubscribers('13800',   1, 200); // 200 subscribers
        $subscribersBatchTwo = $this->makeSubscribers('13800', 201, 300); // 100 subscribers

        $client->request('POST', 'http://222.73.117.158/msg/HttpBatchSendSM',
            Argument::that(function (array $request) use ($subscribersBatchOne) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['account']    == 'account' &&
                $request['pswd']  == 'password' &&
                $request['mobile'] == implode(',', $subscribersBatchOne) &&
                $request['msg'] == 'message' &&
                $request['needstatus'] == false &&
                $request['extno'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));
        $client->request('POST', 'http://222.73.117.158/msg/HttpBatchSendSM',
            Argument::that(function (array $request) use ($subscribersBatchTwo) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['account']    == 'account' &&
                $request['pswd']  == 'password' &&
                $request['mobile'] == implode(',', $subscribersBatchTwo) &&
                $request['msg'] == 'message' &&
                $request['needstatus'] == false &&
                $request['extno'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));

        $this->shouldNotThrow()->duringSend(array_merge($subscribersBatchOne, $subscribersBatchTwo), 'message');
    }

    private function makeSubscribers($prefix, $from, $to)
    {
        $pad = 11 - strlen($prefix);  // each mobile number takes 11 in length

        $subscribers = [];
        for ($i = $from; $i <= $to; $i++) {
            $subscribers[] = $prefix . str_pad($i, $pad, STR_PAD_LEFT);
        }

        return $subscribers;
    }

    //=====================================
    //          Query Quota
    //=====================================
    function it_should_have_quota_queried(ClientInterface $client)
    {
        $client->request('POST', 'http://222.73.117.158/msg/QueryBalance',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['account'] == 'account' &&
                $request['pswd'] == 'password';
            }))->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/data/surplus.txt')));

        $this->queryQuota()->shouldBe(1000);


//        $client->request('GET', 'http://221.179.180.158:8081/QxtSms_surplus/surplus?OperID=account&OperPass=password')
//               ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/data/surplus.xml')));
//
//        $this->queryQuota()->shouldBe(10);
    }

}

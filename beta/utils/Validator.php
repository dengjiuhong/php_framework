<?php
/**
 * UC乐园 - 基础工具  验证工具
 *
 * @category   validator
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace utils;
class Validator
{
    /**
     *    检查是否为手机号
     *
     * @param string $mobi  - 待检查的手机号
     * @return boolean      - 是否通过校验
     */
    public function checkIsMobile($mobi)
    {
        if (empty($mobi)) return false;
        if (strlen($mobi) != 11) return false;
        if (!preg_match('/^1[0-9]{10}$/', $mobi)){
            return false;
        }
        // 过滤掉大众号码，如13800138000等等
        if (in_array(md5($mobi), self::$skipMobis)) return false;
        return true;
    }
    /**
     *    检查传入的参数是否为中文
     * @param String $char
     */
    public function checkIsChinese($char)
    {
        if (empty($char)) return false;
        // 检查是否全部为utf8的中文
        if (!preg_match("/^[\x{4E00}-\x{9FA5}]+$/u", $char)){
            return false;
        }
        return true;
    }
    /**
     *   过滤掉的电话号码的集合
     * @var  array()
     */
    private static $skipMobis = array (
      0 => '76a7a805c7802d3d3fa6b568da0cacfc',
      1 => 'e6138480816e72ae576379ac1f641136',
      2 => '0af11009c43011cbfe23f1acab29ce31',
      3 => '4252358fa719e3fee8be9f22a5b8a0a6',
      4 => 'd5ab09d8fc436802e21ab6d9ba7b4309',
      5 => '4a17cc4aa457b0c6dd2cf23a6868d5d2',
      6 => '1cc934dd4224d141655dda6e83b9033a',
      7 => '38e1111f3d0567f8c72b2f01550febba',
      8 => 'c79782020bd5a7eafad3bce5df634deb',
      9 => '2df96c36241a2dbf190433921349cbb8',
      10 => '150a31331212d4ed3dc14f3a0c8424f5',
      11 => 'dc8129f46411183c14f549830cf78392',
      12 => '9cc7551832ee829c22f37964e91887e5',
      13 => '1487fd46fe415da6a3a3883014a94c15',
      14 => '8084453b5826aae6c3e7fd9cc7788985',
      15 => '76e19590bd32a50f483e00a54916b02c',
      16 => 'c8c86d0d310e0fac84c224d2cb431faa',
      17 => '6465b5c91642de939c226deb53f8fb60',
      18 => '59ed888ca65bebc7a411665f48f96533',
      19 => 'dbe51d25379d362bf8941905e259d03a',
      20 => '4abfb4b4625c27a375a656dc9ad85e70',
      21 => '8e89289fabc71eaf94523e925948d4b3',
      22 => 'bdc48af6086831eeb76b9f4e15890a3a',
      23 => '67d4327eb7a77cf8aa292845a9d5918a',
      24 => 'e6f57aa6f92d6d36a3da9c2e28e89e57',
      25 => 'e15591f10c48eeca8ab976bcc4e35de0',
      26 => '9f18ad7391be9041886e7740397bfcef',
      27 => 'fad078b8ff837f000ef4a5a77e59721b',
      28 => '8990caf1e347573dfb311e68614abbf3',
      29 => '88f078113a8d4ebd0e5d75501ebc6a74',
      30 => '7e52dadbc1cf7f77aabee608a1fd3cda',
      31 => '435b2042ddc3e5b80cff56e7ccaba41d',
      32 => '50309004548676a82ebe7f99372172a1',
      33 => 'afb189030d02d9612417ca1b41a28b48',
      34 => '297784baf7cdeea32732d3c0c13ecf19',
      35 => '8a9b6592b8e911e1368e34b45327b445',
      36 => 'ce72710b60983913812f90622cbc41b3',
      37 => '4bdca16e697c550734f9b92d3f34588d',
      38 => '6ab0f4a1235ba0064bc79f7cff95a1ae',
      39 => '32ebc43ff2dac145d41871200f65510a',
      40 => '6e24d1981c8bcafeb641f13b8c8013bb',
      41 => 'a3054f319e64b2e412aaf9ce45202871',
      42 => 'e9f3b3810c933272a1cf496678cbb082',
      43 => 'bb05e2dc93f3d7ce2e0beb0a208d5942',
      44 => 'd0919ff025ce55f1de01ca1ca54c81bd',
      45 => '3dc39938c1a592b5cb9173fd0b6af21e',
      46 => 'abff53b4b1f55cae045915d572efe9af',
      47 => '7945bd83237335e5376ff44d62e4f0ae',
      48 => '071161b169faec07f2e63b4d16bb8b3b',
      49 => 'e722fa20df4ee42377c59682e84197d7',
      50 => '59e778220e9785bf72d53b5297a45561',
      51 => '2a6ce066dadb583de586debfb74026d5',
      52 => '8c02985d05256dd21926d7ddfa35d222',
      53 => '5b235ab0954977e3dae81eb2f88408cf',
      54 => '555810df4e3fc2778694acc268335062',
      55 => 'aa211415ef906e860c27349f8292c304',
      56 => 'e36e7aae922d5cbbf4bef9439599bff0',
      57 => '3ce8bad1811a918c0f50e2279789dcd5',
      58 => 'ccb70dd3c51f67c4d0d635b4597a4781',
      59 => '9815b5fa6e57a12ce9326f52fa4c49b3',
      60 => '48f6bd70570a3e212c73570398fa9f90',
      61 => 'e35d3af381edf179c9fc2fd14c657fc8',
      62 => '353ffc4782ba4d630ba9f53adeca013e',
      63 => 'adbc91a43e988a3b5b745b8529a90b61',
      64 => 'c6195850a938f1f7bbe78381870ba536',
      65 => 'f9792fe8368933c7c876d3a72825f815',
      66 => '3e065d9612260e497309b1948cbcd464',
      67 => 'fb355c222968d07e70b87527fc854e18',
      68 => '277c8fcff1eac48fc6a335ddaa3bce72',
      69 => '8384e1e99520fdabf3c6adbab6c777e4',
      70 => 'c94bad88f1af4aff5191db1a0606c40e',
      71 => '1c3fe7b96353cb0ff1a22b53ba37e480',
      72 => 'a5ae8639c9808d4ec28e62fa1ebd448b',
      73 => '5cf72e4faa8cf49aa8a94837b5b82625',
      74 => '044fad9d08051cff415c99baa38e8b4c',
      75 => 'c61015f5338427aff647448376a0eb6c',
      76 => '00943f910d9afaa3ce2f70b7c12813bd',
      77 => '04a400a4de3c82a426bb37734ede6343',
      78 => 'e3e3e27f65ef917be59e7798afbb8ed9',
      79 => '140b31bf60cd536dcbb2db6888423282',
      80 => 'a6c0ec3c5240f0c2d48a0ceb300fb559',
      81 => 'd50dd7d52d4016e9a7983d1eaaf766be',
      82 => '47297bf6a1813935624728dd0491a1d5',
      83 => '91b42f31a855c1880e0b59bda4a0ca35',
      84 => '7ed8ccd837f0b79000509bf32f4a0093',
      85 => '79c287ea852fb4e1cddb0f9634a625ff',
      86 => 'b19057771486be24bc3183b9b67adf64',
      87 => '47679e1749bf4b3cd5725454064816bb',
      88 => 'a648651d75d08d73b6d7c466fe620d89',
      89 => 'f1f308c61e0311e2ce269b261a0efb0d',
      90 => '3f12db39083bee8678670771ca12dd62',
      91 => 'a87a3618f3fc45426d6c716202f9da91',
      92 => '4e3835ee53486a17c701a24e22de391c',
      93 => '6cb21bf447f8eb60848e9d3fb4057107',
      94 => 'e156f80b8b686a41c75d4cac88cd38cc',
      95 => '8466af7b03f96d9c36f0f5fcec82d459',
      96 => 'eeef5b152def809a4e78d4928a39423f',
      97 => 'f41a213dcfabf5cd0b82f4c350336fb1',
      98 => '2060b1a18a8415a2bc28ad2cd06e6524',
      99 => 'a523e0fa952828210383dcbab4db5c54',
      100 => '6fada50ff5b45da2ede23922dc49622b',
      101 => '416a0a7911844d96ec15779db20834b3',
      102 => 'e9f2bc2dec6ef6038ab38a9356801f13',
      103 => 'abb06296c9cca9531fbb4966f522e998',
      104 => '87390fef1797e661e3d9b018821e0699',
      105 => 'f22e94bc9161c9f923b205f4ca96cd62',
      106 => '7b2453197913455a5fbb0b113d7a51c5',
      107 => '72cb65294e0e8dd0c01664cb52006cbc',
      108 => 'c1433a9030383cef09fbb1a80ed4c3ee',
      109 => '586d3c9f47f74d346d30e4047c8e17db',
      110 => '6f7c4eb23a47cf1a41b187fb6c85a86a',
      111 => '3ca2c3e30481330c65ff78c8cdd9aa4f',
      112 => 'df7564ce4794d8db93c38eab815c3bc0',
      113 => '3c83cf7661f93c015c23617b71858bf7',
      114 => 'bec4ced42d6c5b7403a3883abe40ff27',
      115 => '2bb64116fc2255231b1882cee36d3b52',
      116 => '7a354fde92aeab6533f8447e4f41c35b',
      117 => '16ef22d635de9872936675fc29aa6fa6',
      118 => '55852dfc865c38c3160b85e3bb0302b6',
      119 => '1501ce13418347ba38ef0b8d2a3d8fa3',
      120 => 'cfc73264248ba68985f6b096b738387b',
      121 => '43b662c544efb12118d0a8dfdcab111d',
      122 => '1fd9b117982c3a48f7a6c18edd05a543',
      123 => '1e44b43d16d529e071423e4718be1117',
      124 => '463adee318d1861ced7bb90f8c769cb1',
      125 => '278011c79b275d1001250ce6ea509991',
      126 => '2c9a24557df56ca87106358b6d5bad36',
      127 => '22642f815c897fe060dc2ed5955df483',
      128 => '5abbc71322a2f2fde5b89238decbd3ce',
      129 => '609edbf03a0f51404155539464e6ebd6',
      130 => 'a6af40bbfeca7121fd119b357ba98eb4',
      131 => '51df71672046aeacd215e86c474ad4db',
      132 => '6f9007644cb5fb97899e2dd66ac7432c',
      133 => '1a3989aa20f39d1de413da7d31b35dbd',
      134 => '41c9f6e8bf95005232c3540c288e1985',
      135 => '573749b3e5b82f88c7ebf86630a63fcf',
      136 => '6776d4f4c6cc2c826088e6726bd56717',
      137 => 'a78fe5573a7c1b5907aaf0ae4b11132c',
      138 => 'e2f3b570a8223a213e4e4e926db1a663',
      139 => '52e209b8a236bf6b753624ab133b3e66',
      140 => '88f65e6ea51a413772a8dc6a416a3fc9',
      141 => '2f9b232e43bbe934b470ad6cd2bb86b0',
      142 => '9d9b1ff1b4416fc3e9108c062837c6ec',
      143 => '0756550199a6b3f83600b598bf99f14f',
      144 => '724fd08a03fdd0285c9b3b743d1ad84c',
      145 => '60515684e866cd3fab4c17616ac31d5a',
      146 => '4d5c1857471e39eb7a293f772519f1be',
      147 => '8bba27da349364f35f25df30e7db48e4',
      148 => '353cf779f03d1d12a8e1cfe75a95961c',
      149 => '2dca5df88f287c0dd7246af6077642b9',
      150 => 'a15bd9350b1fc52c2cf4eb5bc0f00dce',
      151 => '2ab31329238b676e7dc76a02c1156d62',
      152 => '0b43f2855e669dc4feea4e01867c1d4f',
      153 => '18e3c3b8054971b1426c7c3359f1ae42',
      154 => 'adb94daf1a01ad18dc326287b1137be5',
      155 => '34df3e28fc7df4a4e19ee0290ffd7989',
      156 => '2ab297ea98a1ee8240562d2c28f8b3df',
      157 => '5e2ce4cd2f91f3bb94256276aa6a010a',
      158 => 'cc35eac71d74d0ca37298d09cd09c3a4',
      159 => '6bc644e058fce8fc2764d084d325b178',
      160 => 'b3c1998b72b8fe7948e698f516396a5a',
      161 => '33fbaa69f0c1c931312a92d81bb3eeab',
      162 => '3c605d574880f3a83804ec8516c71285',
      163 => '56b4c55df359abbb24ac53a2dfd2561f',
      164 => '44345a5982371fc805b1cf42efa641c9',
      165 => '8a53eebe5ae96f228be7ba46ecf9dc51',
      166 => 'd3d6ef99bacad25d4d7ca78ce57a97ef',
      167 => 'c83719c9b0d0fcda4d90a1bf159211ff',
      168 => 'ae7f5efae89ee9b38f9d1a7577029c0e',
      169 => '39fd473838e41a7570fd0c737fcb259f',
      170 => 'ba04ec0546e26fe9c4f302aa948c5198',
      171 => '97a05b9fca14c2051df99a23c7bd03fb',
      172 => 'eafff2acdf751143e64ce424d94f69e4',
      173 => 'c024462bfcda660f6d88a9106e5d5b7b',
      174 => '4e94d22ff29960a36a19ecaa6f70eebd',
      175 => '1eaf88efd8b7242ba1910a83802ff779',
      176 => 'c1c895d3b1fb557e8a7adfe37712b459',
      177 => 'a8b78350cf450b2b1dc3908175a7ab82',
      178 => '2052196dcd5691280f62229ae1076244',
      179 => '488b2f9d0c8286e998ff9cd79885b5d3',
      180 => '0be6625407764d0096bcaec8161f2e89',
      181 => '634701f5ecd112424b70a685ac04f1a2',
      182 => '5f78622155e5b8a1afba5bafd377eeaf',
      183 => '2e91f88f4f8e6e2316644b2bcf2ab0e7',
      184 => '504d3c91db9b2bef5a120f3853fb2d67',
      185 => '1e93b4b52cb0993b83575586e1472439',
      186 => '59da09df2d9379ad2db4268f1f41ef5b',
      187 => 'd32b2b36780b96802f400c7d0a5c3404',
      188 => 'e0f88ced2aa2e751d47bfa6d26c76d00',
      189 => '696c89873cecc0711b60fdd869b2bdf5',
      190 => '72862dc86de1f753ef92613f4609c8c8',
      191 => '685584dc30ab265e78372404628415a8',
      192 => 'f4e865d49e1db1f4ea353e57ea2309af',
      193 => '2ee38f5844805dfa237cecaae1e1e004',
      194 => '62ec176987615ba26731467edd76bc93',
      195 => '9f01bbfd7c6bfdf1efbd692730bd1782',
      196 => 'a6a1d70a73334d329c3c9109351b2cf6',
      197 => '661d9c598f398ea29b8ba498e549de34',
      198 => '5daad257487f1b493114181a22e37eb5',
      199 => 'f1c5b9bad0c6a69ddb6bc6e763e308f9',
      200 => 'f98c3c72f70e9665a4da585ebda1f70f',
      201 => 'c48ff903cc9b5d02448d304c731588df',
      202 => '08e0d04bf0144382b5476fa0ce57cbd7',
      203 => '59a89f954f5d71958c11d6e255e806fe',
      204 => '6e4a130b5e95a02006dbe94ee2194148',
      205 => 'd7b21ea1aa19e5dc531509015f2da823',
      206 => '51e72bcc482c403e4243e9a7182b5c1c',
      207 => '3a598951b25c49d1477f56d2c053b90d',
      208 => '3506b423c11fce4f482648e23a709907',
      209 => '3f9f909dc00ce102a462632633c6829c',
      210 => '67880ec6601a93e65af026a641713954',
      211 => 'ef4b044d7a0a67b426407b0192fd339e',
      212 => '9252e90b42359fc6b68499dbb01eb1eb',
      213 => 'eb57f1f0ea9826f76c6b14f168855aaf',
      214 => 'ed94b1c2ee723d470585204f0672a3fb',
      215 => 'fd7ad5a411c6c0490dd42873e8e9a4bd',
      216 => '73a15cf66199e6a3204376d0dd843d1f',
      217 => 'ae85b6c142c93f49365a13f65e0a1f37',
      218 => '6eefe43200d163e8a61dbe0dddf80008',
      219 => '25d1c21169e108495ddd9b9343d6e735',
      220 => '927d42ec84c5c2b57992bf5a18421862',
      221 => '29fc2cf06bc8fa5f98b3bffe8108b2a7',
      222 => 'b9127f9a66ecef924247baa4b39334f5',
      223 => 'eb557bd54dd3c987fd11e30da9b9bb6c',
      224 => '9d4efe485163ea0dd6a6dad8a774733a',
      225 => '173ebab2670ed926f38c894279032007',
      226 => '7e6c4ba119dd570a1c5be9818001b785',
      227 => 'fcf761c13e63beffa9f3d1d7096552da',
      228 => '345adf8ec4d8c1d69234b8b7e2a91e65',
      229 => 'e6940a521b104efde3a221bb3ea5dc71',
      230 => '14515d4d62ff124339c0b25eaa7f493a',
      231 => 'bc57cc8676f12cbbab90920886e6e9d4',
      232 => '9d4d02af83f3efee27148d1900131c75',
    );
    /**
     *   获取单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    public function __construct()
    {
        // 初始化
    }
    public static $obj = null;
}

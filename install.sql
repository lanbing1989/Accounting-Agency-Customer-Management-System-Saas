-- MySQL dump 10.13  Distrib 5.7.43, for Linux (x86_64)
--
-- Host: localhost    Database: saas_wsx_tax
-- ------------------------------------------------------
-- Server version	5.7.43-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `annual_reports`
--

DROP TABLE IF EXISTS `annual_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annual_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `reported_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_reports`
--

LOCK TABLES `annual_reports` WRITE;
/*!40000 ALTER TABLE `annual_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `annual_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_templates`
--

DROP TABLE IF EXISTS `contract_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contract_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_templates`
--

LOCK TABLES `contract_templates` WRITE;
/*!40000 ALTER TABLE `contract_templates` DISABLE KEYS */;
INSERT INTO `contract_templates` VALUES (1,2,'模板','涉税专业服务暨代理记账业务委托合同\r\n\r\n委托方：{contact_person} {contact_phone}（以下简称甲方）\r\n\r\n受托方：xx财税服务有限公司（以下简称乙方）\r\n\r\n甲乙方本着诚实守信、平等互惠、双方自愿的原则，根据《中华人民共和国民法典》、《中华人民共和国公司法》、《中华人民共和国会计法》、《中华人民共和国税收征收管法》、《涉税专业服务监管办法（试行）》、《涉税专业服务基本准则（试行）》、《涉税专业服务职业道德守则（试行）》、《代理记账管理办法》及其他相关法规的规定，就甲方委托乙方为其实际控制的{client_name}（公司名称）提供涉税专业服务、企业设立、代理记账等相关事宜，根据双方约定的服务内容签订本合同，以资共同遵守。\r\n\r\n客户告知：甲方应当知悉乙方提供的服务仅为帮助甲方代理记账及进行纳税申报等服务，甲方应当依法履行纳税义务及其他国家法律规定的费用缴纳义务，乙方无法为甲方提供税收豁免服务。\r\n\r\n一、委托业务范围\r\n\r\n主要服务内容：☑纳税申报代理；☑代理建账记账；☑发票服务；☑一般税务咨询；□其他涉税服务。\r\n\r\n套餐类型：{package_type}。\r\n\r\n服务期限：{service_start}至{service_end}共计{month_count}个月（以纳税所属期为准，新公司以税务登记的第一个月为起始纳税所属期，切户的以首次开始工作的申报期为起始纳税所属期）。\r\n\r\n二、服务费用\r\n\r\n本合同总金额为（人民币）￥{segment_fee}，于本合同签订之时一次性付清。\r\n\r\n支付方式：在线支付、银行转账；乙方账号信息：户名：xx财税服务有限公司,开户银行：xx银行xxx支行，银行账号：123456789123456\r\n\r\n三、甲方的责任和义务\r\n\r\n（一）甲方的每项经济业务，必须填制或者取得符合国家统一会计制度规定的原始凭证。\r\n\r\n（二）甲方应归集和整理有关经济业务的原始凭证和其他资料，并于次月5日前提供给乙方。甲方对所提供资料的完整性、真实性、合法性负责，不得虚报、瞒报收入和支出。\r\n\r\n（三）甲方应建立健全与本企业相适应的内部控制制度，保证资产的安全和完整。\r\n\r\n（四）甲方应当配备专人负责日常货币资金的收支和保管。\r\n\r\n（五）涉及存货核算的，甲方负责存货的管理与盘点，应建立存货的管理制度，定期清查盘点存货，编制存货的入库凭证、出库凭证、库存明细账及每月各类存货的收发存明细表，并及时提供给乙方。甲方对上述资料的真实性和完整性负责，并保证库存物资的安全和完整。\r\n\r\n（六）甲方应在法律允许的范围内开展经济业务，遵守会计法、税法等法律法规的规定，不得授意和指使乙方违法办理会计事项。\r\n\r\n（七）对于乙方退回的、要求甲方按照国家统一的会计制度规定进行更正、补充的原始凭证，甲方应当及时予以更正、补充。\r\n\r\n（八）甲方应积极配合乙方开展代理记账业务，对乙方提出的合理建议应积极采纳。\r\n\r\n（九）甲方应制定合理的会计资料传递程序，及时将原始凭证等会计资料交乙方，做好会计资料的签收工作。\r\n\r\n（十）会计年度终了后，乙方将会计档案移交甲方，由甲方负责保管会计档案，保证会计档案的安全和完整。\r\n\r\n（十一）甲方委托乙方开具销售发票的，应符合税收相关法律法规，不得要求乙方虚开发票。\r\n\r\n（十二）甲方应按本协议书规定及时足额支付代理记账服务费。\r\n\r\n（十三）甲方应保证在规定的纳税期，银行账户有足额的存款缴纳税费款。\r\n\r\n三、乙方的责任和义务\r\n\r\n（一）乙方不得采取隐瞒、欺诈、贿赂、串通、回扣、不当承诺、恶意低价和虚假宣传等不正当手段承揽业务；\r\n\r\n（二）不得歪曲解读税收政策；\r\n\r\n（三）不得诱导、帮助委托人实施涉税违法活动；\r\n\r\n（四）乙方根据甲方所提供的原始凭证和其他资料，按照国家统一会计制度的规定进行会计核算，包括审核原始凭证、填制记账凭证、登记会计账簿、按时编制和提供财务会计报告。\r\n\r\n（五）乙方应严格按照税收相关法律法规，在规定的申报期内为甲方及时、准确地办理纳税申报业务。\r\n\r\n（六）涉及存货核算的，根据甲方提供的存货入库凭证、出库凭证、每月各类存货的收发存明细表，乙方进行成本结转。\r\n\r\n（七）乙方应协助甲方完善内部控制，加强内部管理，针对内部控制薄弱环节提出合理的建议。\r\n\r\n（八）乙方应协助甲方制定合理的会计资料传递程序，积极配合甲方做好会计资料的签收手续。在代理记账过程中，应妥善保管会计资料。\r\n\r\n（九）乙方应按时将当年应归档的会计资料整理、装订后形成会计档案，于会计年度终了后交甲方保管。未办理交接手续前，由乙方负责保管。\r\n\r\n（十）委托协议终止时，乙方应与甲方办理会计业务交接事宜。\r\n\r\n（十一）乙方接受委托为甲方开具销售发票的，应按照税收法律法规要求为甲方提供代开发票服务，不得代为虚开发票。\r\n\r\n（十二）乙方对开展业务过程中知悉的商业秘密、个人信息负有保密义务。\r\n\r\n（十三）对甲方提出的有关会计处理的相关问题，乙方应当予以正确解释。\r\n\r\n四、责任划分\r\n\r\n（一）乙方是在甲方提供相关资料的基础上进行会计核算，因甲方提供的记账依据不实、未按协议约定及时提供记账依据或其他过错导致委托事项出现差错或未能按时完成委托事项，由此造成的后果，由甲方承担。\r\n\r\n（二）因乙方的过错导致委托事项出现差错或未能按时完成委托事项，由此造成的后果，由乙方承担。\r\n\r\n五、协议的终止\r\n\r\n（一）协议期满，本协议自然终止，双方如欲续约，须另定协议。\r\n\r\n（二）本合同有效期内，如甲方公司注销，本合同自然终止，服务费用不予退还，互不追究违约责任。\r\n\r\n（三）本合同有效期内，如甲方单方面提出终止合作或解除合同，服务费用不予退还，互不追究违约责任。\r\n\r\n（四）如在合同有效期内，乙方发现甲方存在违法行为或其他不适宜继续提供服务的情况，可单方面提出终止合作或解除合同，乙方不承担违约责任，但应退还剩余时间的服务费。\r\n\r\n六、特别约定\r\n\r\n无论本协议双方做出的任何约定，违反《中华人民共和国民法典》、《中华人民共和国公司法》、《中华人民共和国会计法》、《中华人民共和国税收征收管法》、《涉税专业服务监管办法（试行）》、《涉税专业服务基本准则（试行）》、《涉税专业服务职业道德守则（试行）》、《代理记账管理办法》的，均以相关法律法规为准，合同约定内容无效。\r\n\r\n七、违约责任\r\n\r\n（一）甲方无特殊原因，未支付乙方当期代理服务费，乙方次月不再继续为甲方提供账务处理、纳税申报等代理服务工作，由此而引起的税务机关等相关部门的罚款或其他责任由甲方承担；\r\n\r\n（二）因乙方过错给甲方造成损失的，乙方应承担赔偿责任，但以所收取的该项服务的服务费用为赔偿上限；\r\n\r\n（三）乙方仅对本合同项下服务周期内的工作质量负责，对于本合同项外及服务周期以外，或在服务周期内乙方正式提供服务前的甲方工作成果，乙方均不负责。因任何第三方原因（如其他工商代办、财务服务代理机构）造成的法律、经济责任乙方亦不负责，但可以提供合理且必要的协助；\r\n\r\n（四）若因甲方提供的申报材料不真实、不合法、不准确、不完整、不及时而引起的风险、责任及损失，甲方应全部承担。\r\n\r\n（五）发生本条未约定的违约情形的，违约方应按照《中华人民共和国民法典》的规定承担违约责任；\r\n\r\n（六）不论甲方由于违反本合同任何一条约定需承担违约责任时，乙方均有权向甲方收取由于为甲方提供服务已发生的成本。\r\n\r\n八、其他约定\r\n\r\n（一）本协议的补充条款、附件及补充协议均为本协议不可分割的部分。本协议补充条款、补充协议与本协议不一致的，以补充条款、补充协议为准。\r\n\r\n（二）本协议的未尽事宜及本协议在履行过程中需变更的事宜，双方应通过订立变更协议进行约定。\r\n\r\n（三）甲乙双方在履行本协议过程中发生争议，应协商解决。协商不能解决的，向xxx申请仲裁。\r\n\r\n本协议自双方签字之日起生效。本协议一式两份，双方各执一份。\r\n\r\n（以下无正文，为签章/签字部分）\r\n\r\n委托方：（签字） {signature}\r\n\r\n受托方：（盖章） {seal}\r\n\r\n签约日期：{sign_date}','2025-05-17');
/*!40000 ALTER TABLE `contract_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `remark` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_client` (`tenant_id`,`client_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_agreement`
--

DROP TABLE IF EXISTS `contracts_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts_agreement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `uuid` varchar(64) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `seal_id` int(11) DEFAULT NULL,
  `sign_status` varchar(50) DEFAULT '',
  `sign_image` varchar(255) DEFAULT NULL,
  `sign_time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `sign_date` date DEFAULT NULL,
  `service_period_id` int(11) DEFAULT NULL,
  `service_segment_id` int(11) DEFAULT NULL,
  `content_snapshot` text,
  `contract_no` varchar(32) DEFAULT NULL,
  `contract_hash` varchar(80) DEFAULT NULL,
  `sign_ip` varchar(40) DEFAULT NULL,
  `sign_phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_agreement`
--

LOCK TABLES `contracts_agreement` WRITE;
/*!40000 ALTER TABLE `contracts_agreement` DISABLE KEYS */;
INSERT INTO `contracts_agreement` VALUES (2,2,'cbb2c4025423ebbb',2,1,1,'','signatures/sign_2_1747476428.png',NULL,NULL,'2025-05-17',3,4,'涉税专业服务暨代理记账业务委托合同\r\n\r\n委托方：2 3（以下简称甲方）\r\n\r\n受托方：xx财税服务有限公司（以下简称乙方）\r\n\r\n甲乙方本着诚实守信、平等互惠、双方自愿的原则，根据《中华人民共和国民法典》、《中华人民共和国公司法》、《中华人民共和国会计法》、《中华人民共和国税收征收管法》、《涉税专业服务监管办法（试行）》、《涉税专业服务基本准则（试行）》、《涉税专业服务职业道德守则（试行）》、《代理记账管理办法》及其他相关法规的规定，就甲方委托乙方为其实际控制的1（公司名称）提供涉税专业服务、企业设立、代理记账等相关事宜，根据双方约定的服务内容签订本合同，以资共同遵守。\r\n\r\n客户告知：甲方应当知悉乙方提供的服务仅为帮助甲方代理记账及进行纳税申报等服务，甲方应当依法履行纳税义务及其他国家法律规定的费用缴纳义务，乙方无法为甲方提供税收豁免服务。\r\n\r\n一、委托业务范围\r\n\r\n主要服务内容：☑纳税申报代理；☑代理建账记账；☑发票服务；☑一般税务咨询；□其他涉税服务。\r\n\r\n套餐类型：小规模纳税人。\r\n\r\n服务期限：2025-05-01至2026-04-30共计12个月（以纳税所属期为准，新公司以税务登记的第一个月为起始纳税所属期，切户的以首次开始工作的申报期为起始纳税所属期）。\r\n\r\n二、服务费用\r\n\r\n本合同总金额为（人民币）￥2400.00，于本合同签订之时一次性付清。\r\n\r\n支付方式：在线支付、银行转账；乙方账号信息：户名：xx财税服务有限公司,开户银行：xx银行xxx支行，银行账号：123456789123456\r\n\r\n三、甲方的责任和义务\r\n\r\n（一）甲方的每项经济业务，必须填制或者取得符合国家统一会计制度规定的原始凭证。\r\n\r\n（二）甲方应归集和整理有关经济业务的原始凭证和其他资料，并于次月5日前提供给乙方。甲方对所提供资料的完整性、真实性、合法性负责，不得虚报、瞒报收入和支出。\r\n\r\n（三）甲方应建立健全与本企业相适应的内部控制制度，保证资产的安全和完整。\r\n\r\n（四）甲方应当配备专人负责日常货币资金的收支和保管。\r\n\r\n（五）涉及存货核算的，甲方负责存货的管理与盘点，应建立存货的管理制度，定期清查盘点存货，编制存货的入库凭证、出库凭证、库存明细账及每月各类存货的收发存明细表，并及时提供给乙方。甲方对上述资料的真实性和完整性负责，并保证库存物资的安全和完整。\r\n\r\n（六）甲方应在法律允许的范围内开展经济业务，遵守会计法、税法等法律法规的规定，不得授意和指使乙方违法办理会计事项。\r\n\r\n（七）对于乙方退回的、要求甲方按照国家统一的会计制度规定进行更正、补充的原始凭证，甲方应当及时予以更正、补充。\r\n\r\n（八）甲方应积极配合乙方开展代理记账业务，对乙方提出的合理建议应积极采纳。\r\n\r\n（九）甲方应制定合理的会计资料传递程序，及时将原始凭证等会计资料交乙方，做好会计资料的签收工作。\r\n\r\n（十）会计年度终了后，乙方将会计档案移交甲方，由甲方负责保管会计档案，保证会计档案的安全和完整。\r\n\r\n（十一）甲方委托乙方开具销售发票的，应符合税收相关法律法规，不得要求乙方虚开发票。\r\n\r\n（十二）甲方应按本协议书规定及时足额支付代理记账服务费。\r\n\r\n（十三）甲方应保证在规定的纳税期，银行账户有足额的存款缴纳税费款。\r\n\r\n三、乙方的责任和义务\r\n\r\n（一）乙方不得采取隐瞒、欺诈、贿赂、串通、回扣、不当承诺、恶意低价和虚假宣传等不正当手段承揽业务；\r\n\r\n（二）不得歪曲解读税收政策；\r\n\r\n（三）不得诱导、帮助委托人实施涉税违法活动；\r\n\r\n（四）乙方根据甲方所提供的原始凭证和其他资料，按照国家统一会计制度的规定进行会计核算，包括审核原始凭证、填制记账凭证、登记会计账簿、按时编制和提供财务会计报告。\r\n\r\n（五）乙方应严格按照税收相关法律法规，在规定的申报期内为甲方及时、准确地办理纳税申报业务。\r\n\r\n（六）涉及存货核算的，根据甲方提供的存货入库凭证、出库凭证、每月各类存货的收发存明细表，乙方进行成本结转。\r\n\r\n（七）乙方应协助甲方完善内部控制，加强内部管理，针对内部控制薄弱环节提出合理的建议。\r\n\r\n（八）乙方应协助甲方制定合理的会计资料传递程序，积极配合甲方做好会计资料的签收手续。在代理记账过程中，应妥善保管会计资料。\r\n\r\n（九）乙方应按时将当年应归档的会计资料整理、装订后形成会计档案，于会计年度终了后交甲方保管。未办理交接手续前，由乙方负责保管。\r\n\r\n（十）委托协议终止时，乙方应与甲方办理会计业务交接事宜。\r\n\r\n（十一）乙方接受委托为甲方开具销售发票的，应按照税收法律法规要求为甲方提供代开发票服务，不得代为虚开发票。\r\n\r\n（十二）乙方对开展业务过程中知悉的商业秘密、个人信息负有保密义务。\r\n\r\n（十三）对甲方提出的有关会计处理的相关问题，乙方应当予以正确解释。\r\n\r\n四、责任划分\r\n\r\n（一）乙方是在甲方提供相关资料的基础上进行会计核算，因甲方提供的记账依据不实、未按协议约定及时提供记账依据或其他过错导致委托事项出现差错或未能按时完成委托事项，由此造成的后果，由甲方承担。\r\n\r\n（二）因乙方的过错导致委托事项出现差错或未能按时完成委托事项，由此造成的后果，由乙方承担。\r\n\r\n五、协议的终止\r\n\r\n（一）协议期满，本协议自然终止，双方如欲续约，须另定协议。\r\n\r\n（二）本合同有效期内，如甲方公司注销，本合同自然终止，服务费用不予退还，互不追究违约责任。\r\n\r\n（三）本合同有效期内，如甲方单方面提出终止合作或解除合同，服务费用不予退还，互不追究违约责任。\r\n\r\n（四）如在合同有效期内，乙方发现甲方存在违法行为或其他不适宜继续提供服务的情况，可单方面提出终止合作或解除合同，乙方不承担违约责任，但应退还剩余时间的服务费。\r\n\r\n六、特别约定\r\n\r\n无论本协议双方做出的任何约定，违反《中华人民共和国民法典》、《中华人民共和国公司法》、《中华人民共和国会计法》、《中华人民共和国税收征收管法》、《涉税专业服务监管办法（试行）》、《涉税专业服务基本准则（试行）》、《涉税专业服务职业道德守则（试行）》、《代理记账管理办法》的，均以相关法律法规为准，合同约定内容无效。\r\n\r\n七、违约责任\r\n\r\n（一）甲方无特殊原因，未支付乙方当期代理服务费，乙方次月不再继续为甲方提供账务处理、纳税申报等代理服务工作，由此而引起的税务机关等相关部门的罚款或其他责任由甲方承担；\r\n\r\n（二）因乙方过错给甲方造成损失的，乙方应承担赔偿责任，但以所收取的该项服务的服务费用为赔偿上限；\r\n\r\n（三）乙方仅对本合同项下服务周期内的工作质量负责，对于本合同项外及服务周期以外，或在服务周期内乙方正式提供服务前的甲方工作成果，乙方均不负责。因任何第三方原因（如其他工商代办、财务服务代理机构）造成的法律、经济责任乙方亦不负责，但可以提供合理且必要的协助；\r\n\r\n（四）若因甲方提供的申报材料不真实、不合法、不准确、不完整、不及时而引起的风险、责任及损失，甲方应全部承担。\r\n\r\n（五）发生本条未约定的违约情形的，违约方应按照《中华人民共和国民法典》的规定承担违约责任；\r\n\r\n（六）不论甲方由于违反本合同任何一条约定需承担违约责任时，乙方均有权向甲方收取由于为甲方提供服务已发生的成本。\r\n\r\n八、其他约定\r\n\r\n（一）本协议的补充条款、附件及补充协议均为本协议不可分割的部分。本协议补充条款、补充协议与本协议不一致的，以补充条款、补充协议为准。\r\n\r\n（二）本协议的未尽事宜及本协议在履行过程中需变更的事宜，双方应通过订立变更协议进行约定。\r\n\r\n（三）甲乙双方在履行本协议过程中发生争议，应协商解决。协商不能解决的，向xxx申请仲裁。\r\n\r\n本协议自双方签字之日起生效。本协议一式两份，双方各执一份。\r\n\r\n（以下无正文，为签章/签字部分）\r\n\r\n委托方：（签字） <img src=\"signatures/sign_2_1747476428.png\" style=\"height:60px;\">\r\n\r\n受托方：（盖章） <img src=\"seals/seal_tpl_682858e9c3205.png\" style=\"height:60px;\">\r\n\r\n签约日期：2025-05-17<hr style=\'border:1px dashed #bbb; margin:20px 0;\'>\n<div style=\'font-size:15px;color:#666;\'>\n合同编号：HT20250517001<br>\n合同哈希：ec773205b62cc96dca55872c793c463802f8f72f2d1d1ceea604bb8bfbee6777<br>\n<span>扫码查验真伪：</span>\n<img src=\'qrcode.php?text=http://saas.wsx.tax/contract_verify.php?no=HT20250517001\' height=\'80\'>\n</div>','HT20250517001','ec773205b62cc96dca55872c793c463802f8f72f2d1d1ceea604bb8bfbee6777','223.104.196.38','15020793377');
/*!40000 ALTER TABLE `contracts_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `service_segment_id` int(11) DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `remark` text,
  `is_temp` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `s_key` varchar(64) NOT NULL,
  `s_value` text,
  PRIMARY KEY (`s_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES ('allow_register','0'),('api_access_key',''),('default_package',''),('login_ip_whitelist',''),('max_tenant_count',''),('max_user_count',''),('oss_bucket',''),('oss_endpoint',''),('oss_key',''),('oss_provider',''),('oss_secret',''),('payment_appid',''),('payment_key',''),('payment_provider',''),('platform_announce',''),('platform_email',''),('platform_icp','鲁ICP备2024066831号-2'),('platform_name','Ai代账CRM-SaaS云平台'),('platform_tel',''),('sms_appid',''),('sms_key',''),('sms_provider',''),('sms_sign',''),('smtp_from_email',''),('smtp_from_name',''),('smtp_host',''),('smtp_pass',''),('smtp_port',''),('smtp_secure',''),('smtp_user','');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seal_templates`
--

DROP TABLE IF EXISTS `seal_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seal_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seal_templates`
--

LOCK TABLES `seal_templates` WRITE;
/*!40000 ALTER TABLE `seal_templates` DISABLE KEYS */;
INSERT INTO `seal_templates` VALUES (1,2,'cy','seals/seal_tpl_682858e9c3205.png','2025-05-17');
/*!40000 ALTER TABLE `seal_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_periods`
--

DROP TABLE IF EXISTS `service_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `service_start` date DEFAULT NULL,
  `service_end` date DEFAULT NULL,
  `month_count` int(11) DEFAULT NULL,
  `package_type` varchar(50) DEFAULT NULL,
  `manually_closed` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_periods`
--

LOCK TABLES `service_periods` WRITE;
/*!40000 ALTER TABLE `service_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_segments`
--

DROP TABLE IF EXISTS `service_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `service_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_per_year` decimal(10,2) NOT NULL,
  `segment_fee` decimal(10,2) NOT NULL,
  `package_type` varchar(50) DEFAULT NULL,
  `remark` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_segments`
--

LOCK TABLES `service_segments` WRITE;
/*!40000 ALTER TABLE `service_segments` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_segments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_declare_records`
--

DROP TABLE IF EXISTS `tax_declare_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_declare_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `declare_period` varchar(20) NOT NULL,
  `ele_tax_reported_at` date DEFAULT NULL,
  `personal_tax_reported_at` date DEFAULT NULL,
  `operator` varchar(255) DEFAULT NULL,
  `remark` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_declare_records`
--

LOCK TABLES `tax_declare_records` WRITE;
/*!40000 ALTER TABLE `tax_declare_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_declare_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_logs`
--

DROP TABLE IF EXISTS `tenant_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(64) DEFAULT NULL,
  `detail` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_logs`
--

LOCK TABLES `tenant_logs` WRITE;
/*!40000 ALTER TABLE `tenant_logs` DISABLE KEYS */;
INSERT INTO `tenant_logs` VALUES (1,2,1,'编辑租户信息','原名称：中都财税→中都财税1；原联系人：王士辉→王士辉；原电话：13953715370→13953715370；原邮箱：7298817@163.com→7298817@163.com','2025-05-18 10:30:47'),(2,2,1,'重置管理员密码','管理员ID：2（账号：13953715370）被重置密码','2025-05-18 10:31:03'),(3,2,1,'编辑租户信息','原名称：中都财税1→中都财税服务有限公司；原联系人：王士辉→王士辉；原电话：13953715370→13953715370；原邮箱：7298817@163.com→7298817@163.com','2025-05-18 10:31:08'),(4,1,1,'修改平台设置','','2025-05-18 11:01:38'),(5,1,1,'修改平台设置','','2025-05-18 11:06:43'),(6,1,1,'修改平台设置','','2025-05-18 11:21:17'),(7,2,1,'分配/变更套餐','原套餐ID：1→1；原到期：2027-12-31→2025-12-31','2025-05-18 15:10:53'),(8,2,NULL,'微信下单','订单号：WX202505181511088278，套餐：标准版，金额：1.00元，生成二维码成功，等待支付。','2025-05-18 15:11:08'),(9,2,NULL,'微信下单','订单号：WX202505181511088278，套餐：标准版，金额：1.00元，生成二维码成功，等待支付。','2025-05-18 15:11:16'),(10,2,NULL,'微信下单','订单号：WX202505181513525984，套餐：标准版，金额：1.00元，生成二维码成功，等待支付。','2025-05-18 15:13:52'),(11,2,NULL,'支付成功','订单号：WX202505181513525984，套餐：标准版，金额：1.00元，原到期日：2026-12-31，新到期日：2027-12-31，支付渠道：微信支付，微信流水号：4200002676202505181854301131','2025-05-18 15:13:59'),(12,2,NULL,'微信下单','订单号：WX202505181517577691，套餐：标准版，金额：1.00元，生成二维码成功，等待支付。','2025-05-18 15:17:57'),(13,2,2,'套餐升级下单','由【标准版】升级为【高级版】，原到期日：2027-12-31，补差价：23.6元，订单号：WX202505181519568064','2025-05-18 15:19:56'),(14,2,NULL,'微信下单','订单号：WX202505181519568064，套餐：高级版，金额：23.60元，生成二维码成功，等待支付。','2025-05-18 15:19:56'),(15,2,NULL,'套餐升级','订单号：WX202505181519568064，由【标准版】升级为【高级版】，原到期日：2027-12-31，新到期日：2027-12-31，剩余957天，补差价：23.6元，实际支付：23.60元，支付渠道：微信，微信流水号：4200002675202505185640997831','2025-05-18 15:20:07'),(16,1,1,'修改平台设置','','2025-05-18 20:35:07'),(17,1,1,'修改平台设置','','2025-05-19 20:43:50'),(18,1,1,'重置平台管理员密码','账号：admin','2025-05-20 10:58:23'),(19,2,2,'套餐下单','购买套餐【标准版】，金额：99.00元，订单号：WX202506042304321122','2025-06-04 23:04:32'),(20,2,NULL,'微信下单','订单号：WX202506042304321122，套餐：标准版，金额：99.00元，生成二维码成功，等待支付。','2025-06-04 23:04:32'),(21,2,2,'套餐下单','购买套餐【高级版】，金额：199.00元，订单号：WX202506042309566975','2025-06-04 23:09:56'),(22,2,NULL,'微信下单','订单号：WX202506042309566975，套餐：高级版，金额：199.00元，生成二维码成功，等待支付。','2025-06-04 23:09:56'),(23,2,2,'套餐下单','购买套餐【高级版】，金额：199.00元，订单号：WX202506042312296044','2025-06-04 23:12:29'),(24,2,NULL,'微信下单','订单号：WX202506042312296044，套餐：高级版，金额：199.00元，生成二维码成功，等待支付。','2025-06-04 23:12:30');
/*!40000 ALTER TABLE `tenant_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_orders`
--

DROP TABLE IF EXISTS `tenant_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `order_no` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL,
  `pay_type` varchar(20) DEFAULT 'wechat',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_orders`
--

LOCK TABLES `tenant_orders` WRITE;
/*!40000 ALTER TABLE `tenant_orders` DISABLE KEYS */;
INSERT INTO `tenant_orders` VALUES (1,1,1,99.00,'WX202505181440265505','pending','2025-05-18 14:40:26',NULL,'wechat'),(2,2,1,99.00,'WX202505181442124757','pending','2025-05-18 14:42:12',NULL,'wechat'),(3,2,1,99.00,'WX202505181452236261','pending','2025-05-18 14:52:23',NULL,'wechat'),(4,2,1,99.00,'WX202505181454385407','pending','2025-05-18 14:54:38',NULL,'wechat'),(5,2,1,99.00,'WX202505181454478084','pending','2025-05-18 14:54:47',NULL,'wechat'),(6,2,1,99.00,'WX202505181456028792','pending','2025-05-18 14:56:02',NULL,'wechat'),(7,2,1,99.00,'WX202505181458534165','paid','2025-05-18 14:58:53','2025-05-18 14:59:16','wechat'),(8,2,1,99.00,'WX202505181501418433','pending','2025-05-18 15:01:41',NULL,'wechat'),(9,2,2,399.00,'WX202505181504383821','pending','2025-05-18 15:04:38',NULL,'wechat'),(10,2,1,99.00,'WX202505181506553247','pending','2025-05-18 15:06:55',NULL,'wechat'),(11,2,1,99.00,'WX202505181507024382','pending','2025-05-18 15:07:02',NULL,'wechat'),(12,2,1,99.00,'WX202505181507528237','pending','2025-05-18 15:07:52',NULL,'wechat'),(13,2,1,99.00,'WX202505181508027720','pending','2025-05-18 15:08:02',NULL,'wechat'),(14,2,1,99.00,'WX202505181509394167','paid','2025-05-18 15:09:39','2025-05-18 15:09:49','wechat'),(15,2,1,1.00,'WX202505181511088278','paid','2025-05-18 15:11:08','2025-05-18 15:11:37','wechat'),(16,2,1,1.00,'WX202505181513525984','paid','2025-05-18 15:13:52','2025-05-18 15:13:59','wechat'),(17,2,1,1.00,'WX202505181517577691','pending','2025-05-18 15:17:57',NULL,'wechat'),(18,2,2,23.60,'WX202505181519568064','paid','2025-05-18 15:19:56','2025-05-18 15:20:07','wechat'),(19,2,1,99.00,'WX202506042304321122','pending','2025-06-04 23:04:32',NULL,'wechat'),(20,2,2,199.00,'WX202506042309566975','pending','2025-06-04 23:09:56',NULL,'wechat'),(21,2,2,199.00,'WX202506042312296044','pending','2025-06-04 23:12:29',NULL,'wechat');
/*!40000 ALTER TABLE `tenant_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_packages`
--

DROP TABLE IF EXISTS `tenant_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `max_users` int(11) DEFAULT NULL,
  `max_clients` int(11) DEFAULT NULL,
  `max_agreements` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_packages`
--

LOCK TABLES `tenant_packages` WRITE;
/*!40000 ALTER TABLE `tenant_packages` DISABLE KEYS */;
INSERT INTO `tenant_packages` VALUES (1,'标准版',50,500,1000,99.00),(2,'高级版',100,1000,2000,199.00),(3,'旗舰版',1000,3000,6000,399.00);
/*!40000 ALTER TABLE `tenant_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(64) DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `contact_email` varchar(128) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `is_deleted` tinyint(1) DEFAULT '0',
  `package_id` int(11) DEFAULT NULL,
  `package_expire` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'平台租户','平台管理员',NULL,NULL,1,0,3,'2099-12-31','2025-05-17 09:02:14'),(2,'中都财税服务有限公司','王士辉','13953715370','7298817@163.com',1,0,2,'2027-12-31','2025-05-17 09:21:32');
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_user` (`tenant_id`,`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'admin','$2y$10$.a6rnNHvYOE.uXs9ju5RguU3L3vmA1iJ2N6D4mhJawhHxcWFJsggu','platform_admin'),(2,2,'13953715370','$2y$10$ZAWyZQiG2knBuvsVIVdn/.5cUA3Yl5TyTmz0/Evn8fw3S03xxqlDq','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'saas_wsx_tax'
--

--
-- Dumping routines for database 'saas_wsx_tax'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-05 15:52:25

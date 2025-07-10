-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306 
-- Thời gian đã tạo: Th7 08, 2025 lúc 07:58 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `broccoli`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `item_id`, `quantity`, `added_at`) VALUES
(1, 2, 6, 1, '2025-07-09 00:13:28'),
(2, 2, 7, 6, '2025-07-09 00:18:48'),
(3, 2, 4, 5, '2025-07-09 00:19:13'),
(4, 2, 8, 4, '2025-07-09 00:19:17'),
(5, 2, 1, 4, '2025-07-09 00:33:30'),
(6, 2, 42, 3, '2025-07-09 00:54:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `TT` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `img` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`TT`, `name`, `img`) VALUES
('C', 'CANH', 'c.png'),
('DH', 'ĐẬU HŨ', 'dh.png'),
('KV', 'KHAI VỊ', 'kvc.png'),
('L', 'LẨU', 'l.png'),
('MC', 'MÓN CHÍNH', 'mc.png'),
('MM', 'MÓN MỚI', 'mm.png'),
('N', 'NẤM', 'n.png'),
('RCQ', 'RAU CỦ QUẢ', 'rcq.png'),
('TB', 'TRÀ & BÁNH', 'tb.png'),
('TG', 'TRỘN & GỎI', 'tg.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `items`
--

CREATE TABLE `items` (
  `id` int(15) NOT NULL,
  `TT` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `items`
--

INSERT INTO `items` (`id`, `TT`, `name`, `price`, `quantity`, `description`, `image_url`) VALUES
(1, 'MM', 'ĐẬU HŨ RANG MUỐI KIỂU HONG KONG', 87000, 50, 'Món khai vị kết hợp đậu hũ Triều Châu và nấm đùi gà, mang đến trải nghiệm ẩm thực nhất. Đậu hũ Triều Châu chiên vàng giòn bên ngoài, lớp vỏ dai đặc trưng giữ cho bên trong rất mềm mại và thơm béo. Khi ăn, lớp vỏ giòn rụm tan, vị bùi béo của nhân đậu hũ lan tỏa. Nấm đùi gà sơ chế, xào chín tới để giữ độ giòn sần sật, tạo sự cân bằng về cấu trúc miếng ăn.\r\n\r\nTỏi ớt muối rang kiểu Hong Kong là điểm nhấn với hương tỏi phi thơm lừng, vị cay của ớt và vị mặn mòi của muối rang. Khi kết hợp với đậu hũ và nấm, tỏi ớt muối tạo nên sự phối hợp hài hòa giữa vị mặn, cay, bùi và béo. Thường kèm chén tương ớt pha chua ngọt với chút chanh và đường thốt nốt, giúp làm dịu bớt vị mặn cay và tăng thêm hương vị. Với cách trình bày tinh tế, trang trí vài lá húng quế hoặc rau mùi, món khai vị này không chỉ hấp dẫn thị giác mà còn kích thích vị giác, hoàn hảo để khởi đầu bữa tiệc.', 'DAUHURMHK.png'),
(2, 'MM', 'CHẢ NẤM CHIÊN - SỐT CHUA NGỌT', 92000, 50, 'Chả nấm Bếp nhà làm là món ăn thơm ngon, hấp dẫn ngay từ lần đầu thưởng thức. Những miếng chả được chế biến từ nấm tươi, giữ nguyên độ mềm dẻo và hương vị tự nhiên bùi bùi đặc trưng. Khi chiên vàng giòn, lớp vỏ ngoài của chả nấm giòn rụm, tạo cảm giác thú vị khi cắn, trong khi phần nhân bên trong vẫn mềm mại và thơm béo. \r\nSốt chua ngọt đậm đà được chế biến từ vải thiều chín mọng, nhãn lồng ngọt dịu và vị thanh mát tự nhiên. Hành tây được xào chín tới giữ vị ngọt, còn ớt chuông đỏ và xanh tăng thêm sắc màu. Tất cả hòa quyện hoàn hảo, tạo nên hương vị hài hòa giữa vị bùi béo của nấm và vị chua ngọt của trái cây. Món ăn không chỉ hấp dẫn thị giác với màu sắc tươi, mà còn kích thích vị giác nhờ sự đa dạng về kết cấu và hương vị. Thưởng thức chả nấm chấm cùng sốt vải nhãn sẽ mang lại trải nghiệm ẩm thực tuyệt vời, phù hợp làm khai vị trong bữa tiệc gia đình hoặc họp mặt bạn bè.', 'CHANCSCN.png'),
(3, 'MM', 'MIẾN TRỘN TỨ XUYÊN', 82000, 80, 'Sợi miến khoai tây mềm dai ôm lấy từng ngụm sốt Tứ Xuyên rực đỏ, sóng sánh vị cay tê từ xuyên tiêu, đủ khiến đầu lưỡi reo vui ngay lần chạm đầu tiên. Khi miến trườn qua kẽ răng, hương thơm nồng của tỏi phi hòa lẫn với vị ngọt thanh của nấm kim châm trắng nõn và nấm mỡ béo mềm, tạo ra tầng dư vị chuyển biến liên tục. Ớt chuông đỏ, vàng cắt hạt lựu điểm xuyết sắc màu, thả vào sức sống giòn ngọt, chống lại nét cay nồng, giúp cân bằng khoang miệng. Tất cả nguyên liệu được xào nhanh trên lửa lớn, để miến giữ nguyên độ dai, sốt bám bên ngoài mà không bị quánh. Hạt tiêu xanh nhẹ nhàng phả mùi thơm ấm, khiến từng hơi thở cũng đượm hương. Dầu mè cuối cùng phủ bóng mịn, quyện vào sợi miến như dải lụa cay nồng lấp lánh. Chỉ một đũa đưa lên, vị giác lập tức bùng nổ, ấm nồng, tê, ngọt, béo đan xen kéo dài, để lại dư âm quyến rũ khiến người thưởng thức chỉ muốn gắp thêm mãi. Hậu vị cay dịu, lưu luyến đầu lưỡi.', 'MIENTTX.png'),
(4, 'MM', 'BÚN MÌ VÀNG PHÚC KIẾN', 92000, 80, 'Bún gạo trắng mảnh và mì vàng Phúc Kiến dai dẻo quấn lấy nhau, tựa dải lụa đôi mềm mại, vừa nhẹ vừa đàn hồi. Khi trộn, từng sợi thấm đẫm sốt XO sóng sánh, ngào ngạt hải sản khô, tôm nõn, giăm bông, rượu Thiệu Hưng, hành tím và ớt sa tế, đánh thức khứu giác ngay giây đầu. Nấm mỡ cắt lát dày, xào nhanh trên lửa lớn, tỏa hương béo ngậy, phảng phất vị đất, chen giữa bún mì, tạo khoảng nghỉ dịu ngọt. Giá đỗ tươi xanh, giòn mát, chần chớp nhoáng để giữ nguyên độ tươi, lan tỏa hương thảo mộc, cân bằng nền vị đậm đà mặn cay. Một thìa dầu mè cuối, cùng hành lá thái nhỏ và tiêu xay, phủ lớp hương ấm áp, làm hậu vị kéo dài. Khẩu phần hội tụ tinh bột đủ, protein từ nấm, vitamin A, C, E của giá, cùng khoáng chất trong hải sản, giúp cơ thể nạp năng lượng bền bỉ mà không nặng nề. Mỗi đũa gắp lên, vị giác bừng sáng, thơm, cay, ngọt, béo đan quyện, để lại dư âm tinh tế, đầy lôi cuốn, khiến người ăn nhớ mãi mãi.', 'BUNMVPK.png'),
(5, 'MM', 'CƠM CHIÊN Ô LIU ĐEN TRIỀU SƠN', 95000, 80, 'Cải cà na, còn gọi ô liu đen kiểu Triều Sơn, vốn là gia vị bí truyền của cộng đồng Hoa kiều Chợ Lớn. Những quả ô liu nhỏ được muối ủ chín, thẫm đen, phảng phất mùi thơm nồng vị biển và đậu lên men. Khi bằm sơ rồi phi thơm với dầu, cải cà na lan tỏa hương umami khó tả, quyện vào từng hạt cơm tơi óng ánh. Nấm mỡ thái lát dày, xào nhanh lửa lớn, tiết ra chất béo tự nhiên ngọt thanh, làm chất dẫn để hương ô liu thấm sâu hơn. Hành lá cắt khúc cho vào cuối, giữ nguyên sắc xanh giòn và mùi thơm hăng nhẹ, kết nối tất cả thành bản giao hưởng vị giác. Món cơm chiên giản dị bỗng hóa thành “đặc sản” hiếm có: mặn mà, béo bùi, thoang thoảng vị khói, đọng lại dư âm thanh mát của hành, tạo cảm giác vừa lạ vừa gần. Thực khách chỉ cần một muỗng đầu tiên đã lập tức bị cuốn vào ẩm thực xưa của Chợ Lớn, nơi giao thoa văn hóa Hoa – Việt sống động qua hạt cơm.', 'COMCODTS.png'),
(6, 'MM', 'CANH CỦ SEN NẤM BỤNG DÊ', 110000, 60, 'Củ sen cắt khoanh dày, trắng ngà, giòn ngọt, thả vào nồi canh tiềm đang sôi lăn tăn, vừa đủ để hạt bột bắn nhẹ, phóng thích mùi đất thuần khiết. Đậu phộng rang sơ, vỏ lụa nứt thơm, góp vị béo bùi, tạo độ sánh nhẹ khi hầm lâu. Nấm tuyết trắng trong như vụn mây, nở bung thành từng chùm rì rào, đem lại cảm giác mát lành, hỗ trợ làm dịu cổ họng. Nấm bụng dê – loại nấm hiếm với thân tròn, cắn vào đàn hồi, tiết vị ngọt umami độc đáo, khiến thìa canh thêm chiều sâu. Tất cả hòa tan trong nước dùng rau củ thanh khiết được ninh từ củ cải, ngô ngọt và cà rốt, phảng phất hương táo đỏ và kỷ tử, cho vị hậu ngọt tinh tế. Một nhúm gừng lát mỏng và vài hạt tiêu sọ thả cuối, đủ dậy hương ấm, cân bằng tính hàn của nấm tuyết. Khi múc ra bát, sắc trắng, vàng, nâu xen kẽ, bốc khói nhẹ, mời gọi. Hớp đầu tiên, vị ngọt dịu lan đều, đọng lại vị bùi của đậu và sự giòn man mát của củ sen, mang đến cảm giác thanh tao, giàu dưỡng chất, giúp giải nhiệt và bồi bổ cơ thể sau ngày dài mệt mỏi.', 'CANHCSNBD.png'),
(7, 'MM', 'LẨU SA TẾ', 280000, 40, 'Nước lẩu sa tế dậy mùi ớt khô và sả băm, lớp dầu đỏ, vừa nhìn đã thấy ấm lòng. Khi thêm nước cốt dừa thơm, vị cay lập tức được ôm ấp bởi chất ngọt dịu, tạo nên nền nước dùng hài hòa nhưng vẫn cực kỳ kích thích. Đậu phộng rang giã dập rắc vào nồi sôi, bung hương bùi, hòa vào âm hưởng cay nồng, giúp nước lẩu dày vị, phủ lớp thơm mịn trên mặt. Nhúng từng bẹ cải thảo, thìa canh, cải ngồng xanh mướt, rau muống giòn, nấm đùi gà cắt lát, nấm kim châm trắng muốt và nấm đông cô bóng, ta cảm nhận được sự giao hòa giữa đất, nước và lửa. Rau nhanh chín, giữ độ ngọt nguyên bản, nấm thấm sa tế, cắn vào mọng nước cay the, hậu vị béo nhẹ kéo dài. Bên cạnh, đĩa đậu hũ non và tàu hũ ky cuộn nấm chờ sẵn, sẵn sàng hấp thụ nước lẩu, trở thành miếng ngon mướt mát, đậm đà. Mùi sả, lá chanh và tiêu sọ phảng phất, khiến cả bàn ăn ấm lên, gắn kết mọi người trong bữa chay trọn vẹn, giàu dưỡng chất thanh.', 'LAUST.png'),
(8, 'MM', 'LẨU THẬP BẢO', 310000, 40, 'Mười loại dược liệu quý kỳ tử, táo đỏ, đảng sâm, bạch truật, hoa cúc, cam thảo, hoài sơn, xuyên khung, ý dĩ và gừng khô được ninh chậm, từng phút phóng thích vị ngọt ấm và hương thảo mộc thuần khiết, làm nước lẩu chay sâu lắng, tựa dòng suối trong giữa núi rừng. Khoai môn dẻo bùi rã nhẹ, tạo độ sánh mịn, khiến từng tia nước dùng ôm lấy sợi hủ tiếu trắng hoặc mì vàng dai dẻo. Nấm đùi gà đậm đà, nấm đông cô thoang thoảng mùi gỗ, nấm kim châm giòn ngọt cùng nấm tuyết trong xốp góp bảng chất cảm đa lớp, mỗi loại hút tinh chất dược đăng đắng, trả ra hậu vị umami thanh. Khi rau cần, cải thảo, rau muống, bắp Mỹ và mướp hương lần lượt xuống nồi, màu xanh vàng rực nổi trên nước nâu hổ phách, gợi cảm giác no đủ phong vị đất trời. Đậu hũ non và tàu hũ ky cuộn nấm nhanh chóng thấm nước, cắn vào mềm mướt béo nhẹ. Lá tía tô và một nhúm tiêu sọc thả cuối phút dậy mùi ấm, hòa với khói lẩu bay la đa, gom bạn bè quây quần chung bàn. Nồi lẩu không chỉ đưa đẩy vị giác mà còn hàm chứa ý nghĩa bồi bổ, giải nhiệt, nuôi dưỡng thân tâm giữa nhịp sống bận rộn.', 'LAUTB.png'),
(9, 'KV', 'CƠM CHÁY ÉP GIÒN', 85000, 50, 'Cơm cháy chiên vàng ruộm, mặt ngoài giòn rụm tựa gương nắng, bên trong phảng phất mùi gạo thơm, là nền tảng hoàn hảo để đón nhận tầng hương vị kế tiếp. Lớp chà bông bông xốp, mằn mặn, thoảng vị khói quyện với dầu béo, bám vào từng vảy cơm cháy, tạo cảm giác tan chảy ngay đầu lưỡi. Nấm đông cô rim xì dầu ngọt thanh, thớ nấm dày, mọng nước umami, đem lại chiều sâu đất rừng, cân bằng vị mặn béo của chà bông. Điểm nhấn cuối cùng là mứt hành tây óng ánh, màu hổ phách, mang vị ngọt dịu pha chút chua nhẹ và mùi thơm hăng đặc trưng, khi chạm nhiệt cơm cháy bỗng lan hương caramel, kéo dài hậu vị. Tất cả hòa quyện thành bản giao hưởng kết cấu: giòn – xốp – mềm – dẻo, và hương vị: mặn – ngọt – umami – cay the. Một miếng cắn vào, âm thanh răng rắc vang lên, kế đó là làn sóng vị giác dâng trào, đủ sức đánh thức ký ức về những quầy hàng Chợ Lớn xưa, nơi người ta khéo léo biến nguyên liệu quen thành món ăn “nhỏ nhưng có võ”, làm ấm lòng cả ngày bận rộn.', 'COMCEG.png'),
(10, 'KV', 'BÁNH MÌ NƯỚNG BƠ', 35000, 100, 'Một lát bánh mì ngũ cốc nướng vừa tới, vỏ sậm nâu hổ phách, giòn tan, còn ruột xốp ẩm, tỏa mùi lúa mạch rang phảng phất. Trên mặt bánh là lớp bơ quả bơ nghiền mịn, xanh như mầm non đầu xuân, xen kẽ mấy mẩu thịt quả vàng nhạt giữ nguyên độ béo ngậy tự nhiên. Bơ được dằm nhẹ với chút muối biển để dậy vị, thêm vài giọt nước cốt chanh khử độ ngấy, khiến sắc xanh càng tươi tắn.\r\n\r\nPhủ lên trên là những hạt ớt khô đỏ tươi lấm tấm, như điểm lửa nhỏ trên thảm cỏ non, chạm đầu lưỡi liền bung vị cay ấm, kéo theo hương thơm tinh dầu ớt. Khi cắn, vỏ bánh vỡ “rắc” giòn vui tai, ngay sau đó là dòng bơ mượt mà tan chảy, bèo béo, quyện vị ngọt thanh dịu của quả chín. Ớt khô thoáng qua tạo cú hích vị giác, làm hậu vị kéo dài, vừa ấm vừa mát. Tất cả hòa quyện thành một bản giao hưởng đơn sơ mà tinh tế, cho bữa sáng nhanh gọn nhưng tràn năng lượng, giàu chất xơ, vitamin E, kali và chất béo tốt, nuôi dưỡng cơ thể lẫn tâm hồn.', 'main2.jpg'),
(11, 'KV', 'CHẢ GIÒ PHÔ MAI', 68000, 100, 'Chả giò phô mai bọc bột xù là cuộc hôn phối giữa kỹ thuật chiên giòn Việt và tinh thần “comfort food” quốc tế. Lớp vỏ panko vàng óng, hạt vụn to đều, ôm sát nhân phô mai mozzarella hòa chút cheddar, giữ khối vuông vức, khi thả vào dầu sôi liền nở phồng, phát tiếng “xèo” giòn rộn. Vỏ ngoài đạt độ giòn rụm như vỏ bánh mì tươi, ánh lên màu hổ phách, chỉ chạm nhẹ đã rắc rắc gãy tan. Phía trong, phô mai chảy sánh, kéo sợi dài óng, thơm mùi sữa béo; mỗi lần cắn, dòng phô mai nóng quện lấy vị ngọt béo, phủ lên khoang miệng như tấm lụa mượt.\r\n\r\nĐi kèm là bát sốt mayonnaise mịn như nhung, đánh cùng chanh vàng và mù tạt Dijon nhẹ, tạo vị chua béo thanh, giúp cân bằng dầu chiên. Chấm miếng chả giò vào sốt, vỏ giòn gặp chất kem mát, phô mai và mayo giao thoa, cho cảm giác bùng nổ: đầu lưỡi tan chảy, hậu vị thoảng cay, béo và hơi chua. Món ăn giản dị nhưng giàu năng lượng, lý tưởng cho buổi tụ tập bạn bè hay bữa ăn nhẹ cuối tuần, mang lại trải nghiệm “ngoại vỏ – nội tan” khó quên.', 'CHAGPM.png'),
(12, 'KV', 'CHẢ RAM BROCCOLI', 95000, 100, 'Chả giò khoai môn Broccoli gói trong bánh tráng mỏng tang, ôm sát lớp nhân khoai môn tím bào sợi quyện cà rốt giòn, bắp non ngọt sữa và nấm hương thái mảnh dậy mùi gỗ ấm. Cuốn thành hình ống nhỏ, khi thả vào dầu sôi liền nổi bọt lách tách, vỏ bánh chuyển hổ phách, căng phồng, tỏa hương gạo rang thơm ngậy. Cắn nhẹ nghe “rắc” ròn rã; bên trong, khoai môn dẻo bùi quyện vị ngọt rau củ và umami dịu của nấm, tạo lớp hương vị lan tỏa êm ấm.\r\n\r\nĐĩa chả giò phục vụ cùng cải bẹ xanh non, húng quế, tía tô, diếp cá; sắc xanh mát đối lập vàng giòn, mang đến cảm giác tươi mới. Cuộn chả giò trong lá cải, chấm nước tương tỏi ớt hoặc mắm chay chua ngọt, vị béo giòn lập tức được làm dịu, để lại hậu vị thanh, dài. Món ăn thuần chay này vừa nhẹ bụng vừa đậm đà, thích hợp mở đầu bữa tiệc hay dùng làm món ăn nhẹ chiều muộn, đem lại trải nghiệm “giòn ngoài, mềm trong” đầy cuốn hút.', 'CHARBC.png'),
(13, 'KV', 'NẤM CHIÊN SỐT XO', 95000, 50, 'Nấm linh chi cắt khúc áo nhẹ bột gạo, chiên ngập dầu đến khi vỏ vàng giòn, giữ ruột dai ngọt umami. Ngay lúc ráo dầu, nấm được xóc nhanh trong sốt X.O sánh đặc nấu từ sò điệp khô, tôm nõn, jambon, tỏi và ớt sa tế, khiến bề mặt phủ lớp hổ phách thơm mùi khói biển. Hạt điều rang giã dập rắc lên, thêm độ giòn bùi; ớt khô khoanh đỏ thẫm chích nhẹ vị cay. Hành lá non thả cuối, gặp sốt nóng tỏa hương hăng dịu, mè trắng rang lấm tấm đọng lại vị ngậy. Một miếng cắn, vỏ “rôm rốp” tan, kế đến lớp mặn ngọt béo cay hòa quyện, đánh thức vị giác đầy phấn khích.', 'NAMCSXO.png'),
(14, 'KV', 'SÚP BẮP MĂNG TÂY NẤM TRÙNG THẢO', 85000, 50, 'Súp bắp – măng tây sánh mịn mở ra bằng vị ngọt sữa tự nhiên của hạt bắp vàng óng; một nửa được xay mịn tạo độ sánh, nửa còn lại giữ nguyên giúp món ăn thêm vui miệng. Măng tây cắt khúc thả sau cùng, giữ sắc xanh ngọc và độ giòn thanh, lan hương cỏ non dịu nhẹ. Xen lẫn là sợi nấm trùng thảo cam rực, dẻo dai, tiết vị ngọt umami cùng mùi thảo dược ấm, nâng tầm món súp thành “bát thuốc” tinh tế bồi bổ cơ thể.\r\n\r\nNền nước dùng rau củ được làm đậm bằng chút sữa hạt và bột năng, cho chất súp dày mà không ngấy. Khi múc ra, sắc vàng – xanh – cam hòa trong làn khói mỏng, điểm chút tiêu trắng và ngò rí, hương thơm bừng dậy. Hớp đầu tiên, vị bắp ngọt, măng tây dịu, nấm trùng thảo ấm áp quyện nhau, để lại hậu vị thanh tao, nhẹ bụng nhưng giàu dưỡng chất.', 'SUPBMTNTT.png'),
(15, 'KV', 'TÀU HŨ KY CUỐN KIỂU BẮC KINH', 95000, 70, 'Tàu hũ ky nướng kiểu Bắc Kinh bắt đầu bằng những lá đậu mỏng tang được phơi nắng cho dậy mùi đậu nành, rồi phết lớp dầu mè nhè nhẹ trước khi đặt lên than hồng. Lửa liếm nhẹ làm bề mặt phồng rộp, chuyển màu vàng mật ong, tỏa hương khói thơm quyện vị béo bùi đặc trưng. Khi lá đậu vừa chín tới, đầu bếp nhanh tay cuộn cùng bánh bía tươi còn âm ấm, mềm mịn tựa lụa, giấu bên trong lát dưa leo xanh giòn và sợi hành tươi thái mảnh.\r\n\r\nCắn một miếng, lớp tàu hũ ky giòn rụm vỡ vụn, theo sau là ruột bánh bía ẩm mịn ôm lấy vị mát lành của dưa leo, xen lẫn mùi hăng nhẹ hành tươi lan tỏa. Khói than phảng phất kết hợp với dầu mè tạo hậu vị ấm áp, thanh tao mà vẫn đậm đà. Từng cuốn nhỏ gói ghém đủ giòn – mềm – mát, biến món thuần chay thành trải nghiệm tinh tế, thích hợp làm khai vị hoặc món ăn nhẹ giữa buổi, khiến thực khách vừa no lòng vừa vương vấn hương khói bếp lò truyền thống.', 'TAUHKCKBK.png'),
(16, 'KV', 'BÒ BÍA PHÚC KIẾN', 69000, 50, 'Bánh bía Phúc Kiến mỏng dẻo như tờ lụa gạo, ôm gọn nhân củ sắn xào nấm hương nóng hổi. Củ sắn cắt sợi xào nhanh trên lửa lớn, vẫn giữ độ giòn ngọt, quyện vị umami thanh của nấm, phảng phất mùi gỗ ấm. Đậu phộng rang giã dập rắc đều, thêm chất bùi rôm rốp, làm nhân dày vị và đa tầng kết cấu. Cuốn xong, lớp bánh bía vẫn mềm ẩm, bề mặt hơi bóng, tỏa hương bột mì mới nướng đầy mời gọi. Chấm từng cuốn vào chén giấm ớt đỏ trong, vị chua cay bật dậy, “đánh thức” vị giác, cân bằng chất ngọt củ sắn và béo bùi đậu phộng, để lại hậu vị thanh nhẹ, sảng khoái. Từng cuốn nhỏ gói trọn cảm giác dai – giòn – bùi – cay, hoàn hảo cho bữa khai vị mùa hè hoặc bữa xế không ngấy.', 'BOBIAPK.png'),
(17, 'KV', 'GỎI CUỐN MÙA XUÂN', 72000, 100, 'Fresh Spring Roll mở ra bản hòa âm thanh đạm nhưng quyến rũ: sợi bún trắng mảnh cuộn lỏng lẽo, nhẹ như sợi tơ, thấm vị ngọt gạo thơm; ẩn bên dưới là hai loại đậu hũ tương phản. Đậu hũ non mềm mịn, cắn vào tan như mây, đem lại cảm giác mát lành, trong khi đậu hũ chiên vàng giòn rộp, thơm mùi đậu nành rang, tạo cú “rắc” vui tai. Tất cả được gói trong bánh tráng dẻo trong, điểm xuyết rau thơm và xà lách mướt xanh, mang tới sắc màu mùa xuân.\r\n\r\nKhi chấm vào chén sốt tương đậu nâu óng – pha giữa tương hột, đậu phộng giã và chút ớt xay – vị mặn ngọt béo cay bung nở, ôm lấy bún và đậu, khiến tầng hương vị dày lên, kéo dài hậu vị ấm nhẹ. Một cuốn nhỏ hội đủ dai – mềm – giòn – béo, vừa thanh bụng vừa giàu đạm thực vật, lý tưởng cho bữa khai vị hay bữa xế nhẹ nhàng.', 'GOICMX.png'),
(18, 'KV', 'BÁNH HOA LAN NẤM THÔNG', 105000, 100, 'Bánh giỏ hoa nhỏ xinh như nụ chồi đầu xuân, lớp vỏ bột gạo mỏng tang được hấp chín tới, trắng ngà, xếp nếp tựa cánh hoa ôm lấy nhân rau củ băm nhỏ và đậu xanh nghiền mịn. Khi bẻ nhẹ, vỏ tách thành từng lớp mờ sương, để lộ phần ruột vàng kem, thoảng vị ngọt tự nhiên của hạt đậu quyện chút hành phi. Đi kèm là sốt nấm rừng sóng sánh: hỗn hợp nấm thông cắt hạt lựu, nấm hương khô và nấm mỡ chưng cùng rượu Thiệu Hưng, tỏi băm và bơ lạt, ninh đến khi sánh nâu hổ phách, phảng phất hương gỗ ấm và vị umami sâu. Trên mặt sốt, đầu bếp khéo rắc hạt thông rang vàng, giòn rôm rốp, béo bùi, tạo điểm nhấn tương phản.\r\n\r\nKhi gắp bánh chấm vào sốt, ta cảm nhận lớp vỏ mịn tan, nhân bùi ngọt hòa cùng vị nấm đậm, hậu vị kéo dài nhờ dầu thông thơm thoang thoảng. Hạt thông vỡ nhẹ giữa kẽ răng, để lại dư âm béo ngậy, cân bằng độ sánh của nước sốt. Món ăn tinh tế, vừa thanh nhã vừa đậm đà, gói ghém hương vị núi rừng trong chiếc bánh kiểu cung đình, phù hợp cho buổi trà chiều sang trọng hoặc tiệc chay ấm cúng.', 'BANHHLNT.png'),
(19, 'TG', 'TRÁI CÂY TÔ', 65000, 50, 'Bát salad trái cây rực rỡ như một bảng pha màu mùa hè. Từng miếng dâu đỏ căng mọng ôm lấy hạt trắng li ti, tỏa hương ngọt thơm; lát kiwi xanh lục điểm hạt đen xếp hình ngôi sao, chua mát dịu nhẹ. Miếng xoài vàng óng mượt mà xen lẫn múi cam, bưởi hồng mọng nước, mang vị chua ngọt cân bằng. Nho tím căng tròn và việt quất xanh tím bé xinh len giữa, tạo độ giòn mọng khi cắn. Tất cả hòa quyện, bừng sáng dưới ánh sáng tự nhiên, mời gọi vị giác với sắc màu tương phản. Chỉ cần nhẹ nhàng trộn lên, nước trái cây quyện vào nhau thành lớp syrup tự nhiên, thơm ngát, giúp mỗi muỗng đưa lên môi vừa mát lạnh vừa bùng vị ngọt thanh, chua nhẹ, lại giàu vitamin và chất chống oxy hóa – hoàn hảo cho bữa sáng hoặc món tráng miệng nhẹ nhàng giữa ngày hè.', 'main4.jpg'),
(20, 'TG', 'SALAD DIÊM MẠCH', 80000, 50, 'Salad diêm mạch là “gói pin dinh dưỡng” gọn nhẹ: hạt quinoa cung cấp protein hoàn chỉnh 9 axit amin thiết yếu, cùng chất xơ hoà tan giúp no lâu, ổn định đường huyết và nuôi lợi khuẩn ruột; rau lá xanh (cải bó xôi, rocket) giàu vitamin K, folate hỗ trợ chuyển hoá năng lượng và chắc xương; bơ hoặc dầu ô-liu thêm vào mang chất béo không bão hoà, góp phần giảm cholesterol xấu, bảo vệ hệ tim mạch; hạt hạnh nhân/óc chó rắc bên trên bổ sung omega-3, magiê chống viêm, giảm căng thẳng; quả lựu hoặc cam cung cấp vitamin C tăng đề kháng và hỗ trợ hấp thu sắt thực vật trong quinoa; cuối cùng, nước xốt chanh mật ong vừa cân vị chua ngọt, vừa cung cấp chất chống ô-xy hoá polyphenol. Nhờ sự kết hợp cân đối carb chậm, đạm hoàn chỉnh, chất béo tốt và vi khoáng, salad diêm mạch thích hợp cho bữa trưa eat-clean, thực đơn thuần chay, hỗ trợ giảm cân hoặc hồi phục sau tập luyện mà không gây cảm giác nặng bụng.', 'main1.jpg'),
(21, 'TG', 'TÔ RAU CỦ', 75000, 50, 'Tô rau củ này đóng vai trò như “cục pin dưỡng chất” cân đối: chất béo không bão hòa trong bơ giúp no lâu và giữ đường huyết ổn định; broccoli, củ dền và cà rốt cung cấp vitamin C, beta-caroten cùng polyphenol kháng viêm, tăng cường hệ thống phòng thủ của cơ thể; ô-liu và hạt gai dầu bổ sung omega-3, 9 hỗ trợ hạ cholesterol, bảo vệ tim mạch; lượng chất xơ dồi dào từ các loại củ kích thích nhu động ruột, trong khi betaine của củ dền hỗ trợ gan thải độc; kali trong bơ và magiê từ hạt điều hòa huyết áp, giảm mỏi cơ; cuối cùng, món ăn thuần chay, không gluten, ít carb tinh luyện nên phù hợp nhiều chế độ eat-clean, giảm cân hay duy trì sức khỏe bền vững.', 'main3.jpg'),
(22, 'TG', 'GỎI XOÀI KHÔ MẶN', 82000, 60, 'Xoài sống được gọt vỏ, bào sợi mảnh, giữ nguyên sắc xanh ngọc cùng vị chua giòn đặc trưng, rồi trộn đều với dưa leo, cà rốt và ớt chuông ba màu thái sợi, tạo bức tranh rau củ rực rỡ. Toàn bộ hỗn hợp được áo lớp nước mắm me chay pha đường thốt nốt, tỏi ớt băm và chút gừng giã, cho vị chua – ngọt – cay hài hòa, đánh thức khứu giác ngay tức thì. Điểm khác biệt nằm ở miếng tương đậu nành lên men cắt khối nhỏ, tẩm bột gạo mỏng, chiên ngập dầu đến khi vỏ vàng giòn, thơm nhẹ mùi đậu rang xen chút khói, mang độ “crunch” bùi béo đối lập nền rau trái mát lạnh. Rau mùi và hành phi rắc sau cùng lan hương thảo mộc, kéo dài hậu vị. Mỗi đũa đưa lên là tổ hợp chua thanh, cay nhẹ, ngọt dịu và béo giòn đan xen, vừa kích thích vị giác vừa giàu chất xơ và probiotic. ', 'GOIXKM.png'),
(23, 'TG', 'GỎI BƯỞI RONG SỤN', 95000, 60, 'Gỏi bưởi rong sụn làn tỏa hương vị biển và vườn trong cùng một đĩa xanh tươi. Tép bưởi hồng tách múi, căng mọng, nổ giòn nhẹ trên đầu lưỡi, phóng thích vị chua ngọt mát lành. Xen kẽ là rong sụn giòn sật, ngấm nước sốt chua nhẹ pha chút đường thốt nốt, gợi nhớ hơi thở đại dương. Nấm bào ngư xé sợi chần nhanh, giữ độ dai tự nhiên, mang hậu vị umami cân bằng vị trái cây. Tất cả được áo bởi lớp mè rang béo thơm, kèm rau mùi xắt nhỏ lan hương thảo mộc, làm trọn vẹn tầng hương vị. Một đũa đưa lên, ta cảm nhận sự đan xen giữa giòn, dai, chua, ngọt và béo, cho cảm giác thanh mát nhưng vẫn đủ chiều sâu, lý tưởng cho bữa khai vị hoặc món ăn nhẹ ngày nắng.', 'GOIBRS.png'),
(24, 'TG', 'KIM VẬN CÁT TƯỜNG', 105000, 50, '“Lucky” Tossed là bản hòa tấu giòn – dai – béo – thanh khiến vị giác bừng sáng ngay đũa đầu tiên. Khoai môn cắt sợi chiên giòn vàng, thơm bùi, đối lập độ mát ngọt của củ năng tươi cùng lát củ sen sầy giòn sật. Cải mầm xanh non chen giữa, mang hương diệp lục tươi mới, trong khi hạt thông rang nhẹ bổ sung vị béo bùi tinh tế. Tất cả được quyện trong sốt quýt chua ngọt thơm nhẹ tinh dầu vỏ cam, giúp món ăn thanh khoáng mà không ngấy. Không chỉ ngon miệng, món gỏi còn cung cấp chất xơ, vitamin C, kali và chất béo không bão hòa, hỗ trợ tiêu hóa, giữ đường huyết ổn định và tăng cường sức khỏe tim mạch – xứng danh “Lucky” khi vừa ngon vừa tốt cho cơ thể.', 'KIMVCT.png'),
(25, 'TG', 'DƯA HẤU TÊ CAY', 85000, 50, 'Bát “Watermelon & Cherry Tomatoes Mala” mở đầu bằng miếng dưa hấu đỏ mọng, ngọt mát, đan xen cà chua cherry chua dịu và lát củ sen sầy trắng giòn; tất cả được xóc nhanh trong sốt Tứ Xuyên tê cay, khiến vị ngọt trái cây bật sáng rồi nhường chỗ cho lớp cay tê ấm lan khắp vòm miệng. Rau mùi xắt nhỏ rắc cuối thêm hương thảo mộc mát lành, cân bằng hậu vị. Món ăn không chỉ đánh thức vị giác bằng đối lập “ngọt–cay–giòn” mà còn bổ sung nước, lycopene, vitamin C và chất xơ, giúp bù điện giải, chống ô-xy hóa và hỗ trợ tiêu hoá — lý tưởng cho ngày hè muốn nạp năng lượng sảng khoái mà không nặng bụng.', 'DUAHTC.png'),
(26, 'TG', 'SALAD VƯỜN TRE', 95000, 50, 'Salad “Bamboo Garden” là sự hòa quyện giữa sắc xanh non của xà lách hỗn hợp và những khối đậu hũ trắng mịn đã ngâm tương đậm đà, thấm nhẹ vị umami. Hạt óc chó được áo lớp đường thốt nốt ngào với ngũ vị hương, rang giòn thơm, tạo cú “crunch” bùi béo quyến rũ. Tất cả được phủ nhẹ tương xí muội sánh đỏ hổ phách, chua ngọt dìu dịu, thoang thoảng mùi mơ muối, khiến vị giác bừng tỉnh. Mỗi gắp mang đủ lớp cảm giác: tươi mát, giòn rôm rốp, béo ngậy và chua thanh. Không chỉ ngon miệng, món salad còn cung cấp chất xơ, đạm thực vật, omega-3 và polyphenol, hỗ trợ tiêu hóa, nuôi lợi khuẩn đường ruột, giảm cholesterol “xấu” và tăng cường sức khỏe tim mạch—lựa chọn lý tưởng cho bữa trưa nhẹ hoặc suất ăn eat-clean giàu dinh dưỡng.', 'SALADVT.png'),
(27, 'TG', 'GỎI CỦ HŨ DỪA', 78000, 60, 'Gỏi củ hũ dừa – nấm bào ngư mang lại “combo” dưỡng chất nhẹ bụng mà giàu năng lượng: chất xơ hòa tan trong củ hũ dừa hỗ trợ tiêu hóa trơn tru, ngừa táo bón; nấm bào ngư xé sợi cung cấp beta-glucan và acid amin thiết yếu giúp tăng cường miễn dịch, duy trì khối cơ; đậu phộng rang bùi béo bổ sung protein thực vật cùng vitamin E và chất béo không bão hòa, có lợi cho tim mạch; rau mùi thêm tinh dầu chống ô-xy hóa, hỗ trợ giải độc gan và làm dậy hương. Kết hợp lại, món gỏi vừa thanh mát giòn sật, vừa giúp ổn định đường huyết, cung cấp năng lượng bền, phù hợp thực đơn eat-clean hay bữa khai vị chay lành mạnh.', 'GOICHD.png'),
(28, 'DH', 'ĐẬU HŨ MA BÀ', 85000, 30, 'Đậu hũ kho Tứ Xuyên kết hợp hai kết cấu tương phản: đậu hũ non trắng mịn thấm nước kho sánh, mềm tan như kem, xen giữa miếng đậu hũ già vàng nhạt chắc hơn, tạo “cú nhai” thú vị. Nấm rơm chẻ đôi được xào chớp nhoáng trước, giữ trọn vị ngọt đất, rồi cùng đậu hũ hầm lừ lừ trong nước sốt xì dầu, tương đậu và ớt sa tế. Xuyên tiêu rang dậy mùi, thả cuối để hạt tinh dầu tê ấm lan khắp khoang miệng, hòa với tỏi băm phi vàng thơm phức. Sốt đỏ nâu đặc quánh phủ đều, ôm lấy từng miếng, cho vị cay tê – mặn ngọt – umami đậm đà nhưng không nặng dầu. Món kho giàu đạm thực vật, chất xơ và canxi, hỗ trợ no lâu, tốt cho tim mạch, phù hợp bữa chay cần năng lượng ấm nóng ngày se lạnh.', 'DAUHUMB.png'),
(29, 'DH', 'ĐẬU HŨ NẤM THIẾT BẢNG', 92000, 40, 'Đậu hũ trứng mềm mượt, vàng ươm như kem sữa, được cắt khoanh rồi áp chảo nhẹ để mặt ngoài hơi xém, toả hương thơm trứng dịu. Khi cho vào chảo rau củ xào nóng, từng lát đậu hũ lướt trong hỗn hợp sắc màu: bông cải xanh giòn ngọt, cà rốt thái que cam rực, bắp non và nấm hương phảng phất mùi gỗ ấm. Nước xốt xì dầu nhạt pha chút dầu hào chay và tỏi gừng băm sôi lách tách, thấm đều, giúp đậu hũ giữ nguyên độ mượt mà vẫn ngậm vị umami đậm. Cắn một miếng, lớp vỏ xém nhẹ “rôm rốp” rồi tan, kéo theo vị ngọt rau củ và hương trứng béo nhẹ, để lại hậu vị thanh, không dầu mỡ. Món ăn cung cấp đạm chất lượng, vitamin A, C, chất xơ cùng canxi, thích hợp cho bữa tối thuần chay nhẹ bụng nhưng đủ dưỡng chất.', 'DAUHUNTB.png'),
(30, 'DH', 'CUỘN RONG BIỂN HẤP KIỂU HONG KONG', 110000, 50, 'Tàu hũ cuộn nấm và rong biển hấp kiểu Quảng Đông là sự kết hợp thanh vị nhưng giàu umami. Lá tàu hũ ky mềm mỏng ôm sát nhân nấm hương, nấm linh chi trắng xé sợi và tấm rong biển nori, được cuộn chặt rồi hấp lửa lớn, giữ nguyên độ ngọt tự nhiên và hương biển thoảng nhẹ. Khi mở nắp xửng, cuốn tàu hũ căng bóng, tỏa mùi đậu nành rang quyện với mùi gỗ ấm của nấm. Cải xanh luộc vừa chín tới kê lót bên dưới, sắc lục tươi mát, giòn ngọt làm nền cân bằng. Nước sốt xì dầu đặc biệt ninh cùng nước nấm, gừng và chút dầu mè được rưới lên, thấm sâu vào từng lớp cuộn, cho vị mặn ngọt hài hòa và hậu vị béo thơm. Sợi hành trắng trụng nóng đặt trên mặt, gặp hơi nước liền tỏa mùi hăng dịu, hoàn thiện tầng hương vị. Món ăn ít dầu nhưng dồi dào chất đạm thực vật, chất xơ và iod, thích hợp cho bữa chay ấm bụng, nhẹ mà đủ dưỡng chất.', 'CUONRBHHK.png'),
(31, 'DH', 'NẤM ĐẬU HŨ UM PHÔ MAI', 85000, 35, 'Đậu hũ cắt khối áp chảo vừa xém cạnh rồi kho lửa nhỏ cùng nấm đùi gà thái dọc, để cả hai ngấm nước sốt um phô mai sánh mịn pha từ sữa hạt và phô mai thực vật tan chảy. Khi sôi lăn tăn, phô mai quyện vào mặt đậu, phủ lớp kem vàng nhạt thơm béo; nấm đùi gà giữ độ dai mọng, hút trọn vị umami của nước kho. Hành baro cắt vát thả cuối, gặp hơi nóng tỏa mùi hăng dịu, làm nền hương thảo mộc mát. Vài hạt mè rang rắc lên mặt, điểm chút “crunch” bùi ngậy và mùi thơm rang nhẹ. Múc một miếng, đậu hũ mềm mượt tan dần, vị phô mai béo thanh hòa với ngọt nấm và hậu thơm mè, để lại dư âm ấm áp nhưng không ngấy. Món kho giàu đạm thực vật, canxi và chất xơ, lý tưởng cho bữa chay cần năng lượng ấm bụng.', 'DAUHUNUPM.png'),
(32, 'DH', 'TÀU HŨ KY CUỘN NẤM SỐT TIÊU ĐEN', 92000, 45, 'Tàu hũ ky mềm mỏng được ngâm cho bung sợi, sau đó trải phẳng, ôm lấy nhân nấm đùi gà thái sợi dai ngọt và đậu que xanh giòn, cuộn chặt thành những khúc nhỏ đều tăm tắp. Cuốn xong, đầu bếp hấp nhanh để nhân chín vừa, rồi áp chảo nhẹ cho mặt ngoài vàng rám, tỏa hương đậu nành rang quyện mùi nấm ấm. Khi dọn, từng cuốn được phủ sốt tiêu đen sánh mịn nấu từ nước tương, tiêu xay thô và chút bơ thực vật, cho vị cay nồng lan tỏa nhưng không gắt, thấm sâu vào các lớp tàu hũ ky. Hạt tiêu lấm tấm trên bề mặt cùng vài cọng hành baro cắt vát, tạo điểm xanh thanh mát và hương thảo mộc nhẹ. Cắn miếng đầu, lớp vỏ tàu hũ ky hơi giòn rồi mềm dần, nhân nấm – đậu que ngọt umami hòa trong sốt tiêu cay thơm, để lại hậu vị ấm và bùi. Món ăn ít dầu, giàu đạm thực vật và chất xơ, phù hợp cho bữa chay cần món chính đậm đà nhưng vẫn thanh nhẹ.', 'TAUHKCNST.png'),
(33, 'DH', 'ĐẬU HŨ MUỐI SẢ', 65000, 70, 'Đậu hũ trắng mềm được cắt khối vuông, áo một lớp bột mỏng rồi chiên ngập dầu đến khi mặt ngoài phồng giòn, chuyển màu vàng mật ong, tỏa hương đậu nành rang. Ngay lúc vớt ra, đầu bếp xóc nhanh cùng “muối sả” – hỗn hợp sả băm, lá chanh và ớt sừng giã nhỏ, rang khô cùng muối biển cho hạt muối kết tinh bám quanh sợi sả cháy giòn. Lớp muối sả lấp lánh ôm sát miếng đậu, mỗi lần cắn nghe “rắc” vui tai rồi lập tức bung hương sả thơm, lá chanh the mát và vị mằn mặn cay nhẹ. Bên trong, lõi đậu hũ vẫn mềm mượt, tương phản hoàn hảo với vỏ giòn rụm. Món ăn ít dầu đọng nhờ bước ráo dầu kỹ, giàu đạm thực vật và tinh dầu citronella kháng khuẩn tự nhiên, thích hợp dùng kèm cơm trắng hoặc làm món nhắm nhẹ, mang đến trải nghiệm “giòn ngoài – mềm trong” đậm đà hương vị Việt.', 'DAUHUMS.png'),
(34, 'DH', 'TÀU HŨ KY HẤP TÀU XÌ', 85000, 40, 'Tàu hũ ky tươi được cuốn thành từng cây nhỏ, căng mịn như dải lụa đậu nành. Khi hấp lửa lớn, lá đậu bung nhẹ, giữ nguyên mùi thơm gạo nếp phảng phất và độ mềm dai tự nhiên. Nước sốt tàu xì – hòa giữa tương đậu đen lên men, gừng, tỏi và chút ớt sa tế – được nấu sánh, rồi rưới thật nóng lên cuốn tàu hũ ky vừa chín tới. Hạt tàu xì lấm tấm bám vào mặt cuốn, lan vị mặn ngọt, umami sâu cùng hơi cay ấm của gừng, tạo chiều sâu hương vị mà vẫn thanh nhẹ, không ngấy dầu. Cắn một miếng, lớp đậu ky mềm dai thấm nước sốt đậm, thỉnh thoảng gặp hạt đậu đen lên men vỡ nhẹ, dậy mùi khói đậu độc đáo. Món ăn giàu đạm thực vật và probiotics, phù hợp cho bữa chay ấm bụng.', 'TAUHKHTX.png'),
(35, 'N', 'MƯỚP & NẤM MỐI HẤP LÁ SEN', 115000, 50, 'Món mướp và nấm mối hấp cùng miến gói trong lá sen khô mang đến trải nghiệm ẩm thực thanh tao. Lá sen khô thoảng mùi thơm, khi khui gói lan tỏa hương sen nhẹ. Bên trong, sợi miến trắng, mềm mượt, hút trọn vị ngọt tự nhiên từ nước hấp. Mướp xanh sau khi hấp vẫn giữ độ giòn và vị ngọt thanh, tạo điểm nhấn tươi mát. Nấm mối vàng nhạt, dai giòn, khi ăn tỏa vị umami đằm, kết hợp cùng vị đất của nấm rừng. Miến quấn lấy mùi ngai ngái của lá sen, hòa với vị bùi của nấm và ngọt của mướp tạo nhiều lớp hương vị. Từng miếng mướp mềm, nấm giòn, miến mềm mại mang cảm giác dịu dàng nhưng đủ độ đậm đà. Mỗi lần thưởng thức, cảm giác hòa quyện tự nhiên, nhắc nhở về hương vị mộc mạc của thiên nhiên. Món thường được dọn nóng, kèm chén nước chấm chua cay hoặc tương ớt để tăng hương vị sinh động. Sự hòa quyện giữa lá sen, miến, mướp và nấm mối tạo món ăn giản dị mà tinh tế, rất phù hợp bữa cơm gia đình ấm cúng.', 'MUOPNMHLS.png'),
(36, 'N', 'RONG BIỂN CUỘN NẤM KHO TIÊU', 85000, 45, 'Cuốn rong biển om nấm rơm tiêu xanh đem tới trải nghiệm ấm nồng giữa ngày se lạnh. Lá rong biển nori được cuộn chặt quanh nhân đậu hũ non tán mịn trộn cà rốt băm và hạt sen nghiền, tạo khối tròn đều, rồi áp chảo nhẹ cho mặt ngoài thơm khói. Khi hầm, từng cuốn ngập trong nước dùng ninh từ nấm hương khô, hành baro và xì dầu, thêm chùm tiêu xanh đập dập để tinh dầu nồng the lan tỏa. Nấm rơm cắt đôi thả vào sau, giữ trọn vị ngọt đất và độ đàn hồi. Sau hơn mười phút lửa liu riu, nước om sánh nâu ôm sát cuốn, hạt tiêu xanh lấm tấm bám mặt, tỏa mùi cay ấm quyện hương biển. Cắn một miếng, rong biển mềm dai, nhân đậu hũ béo mượt hòa vị umami nấm và cay tê dịu, để lại hậu vị ấm áp nhưng thanh nhẹ, giàu đạm thực vật, chất xơ và khoáng chất hỗ trợ tiêu hóa, bảo vệ tim mạch.', 'RONGBCNKT.png'),
(37, 'N', 'NẤM NƯỚNG XÁ XÍU', 83000, 55, 'Nấm đùi gà tươi được ướp nhẹ xì dầu nhạt, dầu hào chay và rượu Thiệu Hưng, rồi rắc tiêu sọ xay thô để thấm sâu từng thớ. Sau đó, đầu bếp xiên nấm đặt lên vỉ than hoa rực đỏ; lửa liếm mặt nấm, tạo vân nâu caramen bóng mỡ và hương khói cổ vị Canton. Khi mặt ngoài vừa chín xém, nấm được phết hỗn hợp dầu mè pha mật mạch nha, giúp giữ ẩm, đồng thời tạo lớp men óng dịu, thoảng mùi vừng rang.\r\n\r\nCải xanh chần nhanh trong nước sôi, vẫn giữ sắc lục ngọc và độ giòn ngọt, bày lót đáy dĩa để hứng lấy nước nấm than chảy ra. Nấm đùi gà sau khi nướng được cắt chéo, xếp tầng như cánh quạt, trên rắc thêm mè trắng rang lấm tấm, mang vị bùi và điểm nhấn thị giác. Cắn một miếng, vỏ nấm hơi cháy giòn rồi mềm dần, tiết vị ngọt umami đậm, hòa hương khói và dầu mè béo thơm; cải xanh mát giòn cân bằng hậu vị, khiến món ăn vừa ấm nồng vừa thanh nhẹ, rất hợp để khơi màn bữa chay phong vị Quảng Đông.', 'NAMNXX.png'),
(38, 'N', 'NẤM & CỦ SEN CUNG BẢO', 97000, 50, 'Nấm đùi gà thái khúc, củ sen cắt khoanh mỏng được chần nhanh để giữ độ giòn, rồi xào trong chảo gang bốc khói cùng tương đậu, giấm gạo, đường thốt nốt và tỏi gừng, tạo nền Gong Bao ngọt mặn cay hài hòa. Ớt khô Tứ Xuyên, ớt hiểm tươi và chút hoa tiêu rang thả vào phút cuối giúp vị cay tê lan nhanh nhưng không gắt. Hạt điều rang giòn rắc đều, phủ thêm lớp béo bùi và điểm crunch vui tai. Miếng nấm mềm ngọt umami, củ sen giòn sật, kết hợp vị chua ngọt cay đặc trưng, để lại hậu vị thơm khói “bắt cơm”.\r\n\r\nKhông chỉ ngon miệng, món xào còn giàu beta-glucan và vitamin B từ nấm, chất xơ kháng tinh bột trong củ sen giúp ổn định đường huyết, cùng chất béo không bão hòa của hạt điều hỗ trợ tim mạch. Nhờ vậy, đây là lựa chọn đậm đà nhưng vẫn lành mạnh cho bữa chay cần năng lượng ấm nóng.', 'NAMCSCB.png'),
(39, 'N', 'NẤM ĐÙI GÀ RIM TƯƠNG ĐẬU', 85000, 40, 'Nấm đùi gà to bản được khía nhẹ, ướp tương đậu lên men pha chút tỏi gừng, rồi om lửa liu riu cùng nước rau củ cho đến khi thớ nấm đổi màu nâu óng và thấm vị umami sâu. Lá bắp cải cuộn quanh mép nồi, mềm dần trong nước om, vẫn giữ sắc lục ngọc và độ ngọt thanh, đồng thời hấp thu phần nước sốt đậm đà. Khi dọn, nấm cắt xiên, xếp chồng tựa cánh quạt, bắp cải lót nền, mặt trên rắc mè rang lấm tấm, thoang thoảng hương vừng béo. Cắn một miếng, lớp ngoài nấm hơi dai rồi tan, vị tương đậu mặn ngọt hài hòa hòa quyện cùng ngọt mát bắp cải; mè rang vỡ nhẹ tạo điểm “crunch” và hậu vị thơm bùi. Món kho ít dầu nhưng giàu đạm thực vật, beta-glucan và chất xơ, giúp no lâu, hỗ trợ tim mạch, thích hợp cho bữa chay ấm bụng mà vẫn nhẹ nhàng.', 'NAMDGRTD.png'),
(40, 'N', 'NẤM MỐI XÀO BƠ TỎI', 120000, 60, 'Nấm mối đen tươi được cắt gốc, lau sạch rồi áp chảo bơ sữa thơm béo cùng tỏi băm; bơ sôi lách tách, tỏi chuyển vàng, tinh dầu thấm sâu vào từng sợi nấm, làm dậy mùi đất ngọt đặc trưng. Mặt nấm xém nhẹ caramen, giòn rìa nhưng ruột vẫn mọng nước umami. Khi dọn, rưới thêm ít bơ tỏi nóng và rắc mùi tây thái nhỏ, hương bơ quyện tỏi lan khắp, kích thích khứu giác. Cắn miếng đầu, lớp ngoài hơi cháy giòn bung vị tỏi, kế đến phần thân nấm mềm dai, ngọt thanh, để lại hậu vị béo thơm mà không ngấy. Món ăn giản dị nhưng giàu đạm thực vật, chất xơ và chất chống ô-xy hóa, lý tưởng làm khai vị ấm nồng trong ngày se lạnh.', 'NAMMXBT.png'),
(41, 'RCQ', 'CỦ SEN XÀO NẤM TRÙNG THẢO & HẠT THÔNG', 110000, 50, 'Củ sen cắt lát mỏng giòn sật được xào nhanh trên lửa lớn cùng măng tây xanh non, giữ nguyên sắc lục ngọc và vị ngọt mát. Mộc nhĩ ngâm nở thái sợi đan xen, mang độ dai nhẹ và hương gỗ thanh. Sợi nấm trùng thảo cam rực thả vào phút cuối, tạo điểm nhấn dẻo ngọt umami và bổ sung lớp thảo dược ấm. Khi chảo bắt đầu dậy khói, đầu bếp rưới hỗn hợp xì dầu nhạt, dầu hào chay và chút rượu Thiệu Hưng, giúp hương lan nhanh mà không ngấy dầu. Hạt thông rang vàng rắc lên sau cùng, vỡ giòn “lách tách”, tỏa vị béo bùi tinh tế, làm tròn khẩu vị. Món xào cân bằng ba kết cấu: giòn củ sen, dai nấm, béo hạt; đồng thời cung cấp chất xơ kháng tinh bột, beta-glucan, cùng omega-3 thực vật, thích hợp cho bữa chay cần năng lượng thanh nhẹ nhưng giàu dưỡng chất.', 'XAODB.png'),
(42, 'RCQ', 'CÀ TÍM TỨ XUYÊN', 65000, 50, 'Cà tím cắt miếng vừa ăn được áo lớp bột mỏng rồi áp chảo lửa lớn, mặt ngoài xém nâu thơm khói, ruột vẫn mềm mọng. Nấm đùi gà thái khúc xào sơ, giữ độ dai ngọt umami, sau đó cùng cà tím lướt nhanh trong sốt Tứ Xuyên pha tương đậu, giấm gạo, đường thốt nốt. Xuyên tiêu rang dậy mùi tê ấm, thả cuối để tinh dầu tỏa khắp, tạo cảm giác “tê–cay” đặc trưng mà không gắt. Hành lá cắt khúc xanh non, gặp sốt nóng liền tỏa hương hăng dịu, cân bằng vị đậm đà. Múc một miếng, cà tím mềm tan, ngấm vị mặn ngọt cay nồng; nấm đùi gà dai giòn theo sau, điểm xuyết hạt xuyên tiêu lấm tấm kích thích đầu lưỡi. Món xào ít dầu, giàu chất xơ và polyphenol, lý tưởng cho bữa chay cần chút ấm nồng phong vị Tứ Xuyên.', 'CATTX.png'),
(43, 'RCQ', 'RAU HẤP THẬP CẨM & KHO QUẸT', 85000, 100, 'Các loại rau củ như bông cải xanh, cà rốt, đậu que, bắp cải tím và su hào được hấp trong xửng đến khi vừa chín tới, vẫn giữ độ giòn và màu sắc tươi tắn. Kho quẹt chay làm từ đường thốt nốt thắng chín đến khi chuyển sang màu nâu cánh gián, sau đó thêm nước tương, nước dừa tươi, tỏi băm và nấm mèo xay nhuyễn. Nấm mèo giúp tạo vị umami đậm đà thay thế cho tôm khô, còn nước dừa và đường thốt nốt mang đến độ ngọt dịu, hơi béo, sánh mượt. Khi chấm rau, bạn sẽ cảm nhận vị ngọt thanh của rau hấp hòa cùng vị mặn ngọt, mùi thơm của tỏi phi và nấm mèo. Món chay này không chỉ đảm bảo dinh dưỡng, màu sắc bắt mắt mà còn mang đến hương vị hài hòa, thích hợp cho bữa ăn gia đình hoặc tiệc chay nhẹ nhàng.', 'RAUHTC.png'),
(44, 'RCQ', 'CẢI THÌA XÀO XÌ DẦU', 45000, 50, 'Gailan, hay còn gọi là cải làn, cùng với cải thìa được chần sơ trong nước sôi, giúp giữ được độ xanh tươi và giòn sần sật. Sau khi vớt ra, rau được để ráo rồi xếp gọn trên đĩa. Phần sốt dầu hào được chế biến từ dầu hào đặc sánh, chút tỏi phi thơm, một ít nước đường để tạo độ ngọt dịu, và rưới nhẹ lên bề mặt rau. Khi thưởng thức, miếng rau giòn ngọt, quyện với vị mặn ngọt đậm đà của dầu hào và mùi tỏi phi nồng ấm, đem lại cảm giác thanh nhẹ nhưng vẫn đậm đà hương vị. Món ăn này không chỉ giàu vitamin và khoáng chất từ rau xanh mà còn là lựa chọn hoàn hảo để cân bằng bữa ăn, giúp bữa cơm thêm phần tươi mát và hấp dẫn.', 'CAITXXD.png'),
(45, 'RCQ', 'MĂNG TÂY XÀO TỎI', 60000, 50, 'Măng tây xào tỏi là món ăn nhẹ nhàng, tập trung vào hương vị tươi mát và độ giòn đặc trưng của măng tây. Những cây măng tây xanh thẫm, mập mạp khi chín vẫn giữ được độ giòn sần sật, kết hợp với mùi thơm nồng nàn của tỏi đã qua sơ chế. Màu xanh tươi tắn của măng tây xen lẫn những mảng vàng ươm của tỏi phi tạo nên tổng thể bắt mắt, gợi cảm giác thanh đạm và nhẹ nhàng. Khi thưởng thức, miếng măng tây giòn ngọt, thoảng chút tinh dầu của tỏi, mang đến dư vị thanh thoát nhưng không kém phần đậm đà, phù hợp cho bữa ăn gia đình hoặc những lúc muốn ăn nhẹ mà vẫn đủ chất.', 'MANGTXT.png'),
(46, 'RCQ', 'BÔNG CẢI XANH NƯỚNG SỐT TƯƠNG MÈ', 45000, 50, 'Bông cải non sau khi được nướng trên than hoa giữ trọn vẹn độ giòn sần và màu xanh tươi mướt, tạo nên sự hấp dẫn ngay từ ánh nhìn. Lớp vỏ ngoài hơi cháy xém nhẹ, phảng phất hương khói đặc trưng, khi cắn vào sẽ cảm nhận vị ngọt tự nhiên của rau kết hợp cùng độ giòn chắc. Sốt tương mè sánh mịn, ngậy béo nhưng không quá nặng, bao phủ từng cọng bông cải bằng hương vị mè rang thơm lừng và vị mặn dịu của tương, tạo nên sự cân bằng hoàn hảo giữa thanh và đậm. Những hạt thông giòn tan điểm xuyết trên đĩa, tỏa hương bùi bùi nhẹ nhàng, mỗi miếng bông cải khi chấm vào sốt tương mè rồi thêm chút hạt thông sẽ mang đến trải nghiệm kết hợp thú vị: dẻo dai, giòn sật, béo ngậy và hơi khói. Món ăn này không chỉ giàu dinh dưỡng mà còn khiến bữa cơm thêm phần tinh tế, sang trọng.', 'BONGCXSM.png'),
(47, 'RCQ', 'KHOAI MÔN HẤP KIỂU KHÁCH GIA', 95000, 50, 'Khoai môn hấp kiểu khách gia là sự kết hợp tinh tế giữa vị ngọt bùi của khoai môn và độ mềm mại của tàu hũ ky, hòa quyện cùng sắc đỏ đậm của chao chấm. Những lát khoai môn sau khi hấp chín vẫn giữ được độ mềm nhưng không quá nát, toát lên hương thơm êm dịu đặc trưng. Tàu hũ ky cuộn gọn bên trong tạo điểm nhấn béo nhẹ, dai dai và thấm đượm hương chao đỏ nồng nàn. Khi chấm vào chao, độ mặn ngọt hài hòa của tương đỏ lan tỏa, nâng vị ngọt tự nhiên của khoai lên một tầng mới đầy hấp dẫn. Món ăn có màu sắc bắt mắt: khoai môn trắng ngà, tàu hũ ky vàng nhạt và chao đỏ rực, khiến người thưởng thức vừa nhìn đã thấy quyến rũ. Tổng thể mang đến trải nghiệm dịu dàng nhưng đậm đà, thanh mát xen lẫn vị chua ngọt đặc trưng của ẩm thực Hakka, thích hợp cho những bữa ăn gia đình ấm cúng hoặc tiệc chay nhẹ nhàng.', 'KHOAIMH.png'),
(48, 'MC', 'CƠM CHIÊN DƯƠNG CHÂU', 78000, 40, 'Cơm chiên Dương Châu chay mang đến hình ảnh rực rỡ với từng hạt cơm tơi, vàng óng ánh kết hợp cùng màu cam bắt mắt của cà rốt thái hạt lựu, sắc xanh mướt của đậu bo và hành lá cắt khúc. Tàu hũ ky được chiên sơ, tạo độ dai mềm vừa phải, thấm đượm hương thơm dịu nhẹ; nấm đùi gà xé sợi, cho vị umami tự nhiên và độ giòn sần sật. Khi thưởng thức, mỗi miếng cơm hội tụ đủ hương vị: cơm ngọt bùi, nấm đùi gà đậm đà, tàu hũ ky nhẹ nhàng, xen lẫn vị tươi ngon của rau củ. Hành lá xanh tươi điểm xuyết tạo mùi thơm thanh, kích thích vị giác. Tổng thể món ăn không chỉ đẹp mắt mà còn cân bằng dinh dưỡng, từ chất xơ, vitamin đến protein thực vật, rất thích hợp cho bữa cơm chay gia đình hoặc bữa trưa nhanh gọn mà vẫn đầy đủ hương vị.', 'COMCDC.png'),
(49, 'MC', 'CƠM TRÁI THƠM', 95000, 45, 'Cơm chiên dứa (Pineapple Fried Rice) thể hiện sự hòa quyện hài hòa giữa vị ngọt thanh cổ điển của thơm và hương nồng ấm của bột cà ri lan tỏa. Hạt cơm vàng ươm, tơi từng hạt, xen lẫn những miếng thơm chín mọng, kích thích vị giác ngay từ ánh nhìn đầu tiên. Đậu bo xanh mướt và cà rốt được cắt khúc nhỏ, giữ nguyên độ giòn tươi, tạo điểm nhấn màu sắc sống động cho tổng thể. Hạt điều rang giòn rụm rắc nhẹ phía trên không chỉ làm tăng độ béo bùi mà còn mang đến cảm giác phong phú khi thưởng thức. Khi ăn, bạn sẽ cảm nhận vị ngọt dịu của dứa phối cùng vị cay nồng nhẹ của cà ri, xen kẽ độ giòn thơm của hạt điều và rau củ, tạo nên một món ăn vừa đầy đặn chất xơ vừa đậm đà hương vị, thích hợp cho bữa trưa hoặc bữa tối gia đình.', 'COMTT.png'),
(50, 'MC', 'CƠM LÁ SEN', 98000, 55, 'Cơm lá sen mang đến hương thơm dịu dàng, đượm mùi sen tự nhiên lan tỏa từng lớp lá gói. Hạt cơm nếp dẻo, ngấm vị tinh túy từ lá sen, phối hợp cùng những hạt sen bùi béo, mang lại độ giòn nhẹ và vị ngọt thanh. Những viên đậu bo xanh tươi, cà rốt cam rực rỡ và tàu hũ ky dai mềm thêm sắc màu sinh động cho món ăn, đồng thời cân bằng độ béo và thơm của hạt sen. Khi chấm đôi đũa khẽ tách lớp lá, từng hạt cơm tơi ra, phảng phất mùi sen dịu, hòa quyện với vị ngọt tự nhiên của rau củ và độ umami nhẹ nhàng từ tàu hũ ky. Mỗi miếng cơm khi thưởng thức đều mang đến cảm giác ấm áp, tinh tế và rất thanh đạm, thích hợp cho bữa trưa hoặc bữa xế nhẹ nhàng mà vẫn đầy đủ dinh dưỡng.', 'COMLS.png'),
(51, 'MC', 'CƠM CHIÊN KHÔ MẶN', 86000, 50, 'Cơm chiên tương đậu lên men chiên tạo nên màu vàng nâu bắt mắt, từng hạt cơm tơi, thấm đều vị đậm đà đặc trưng của tương. Cải xà lách xanh tươi được cắt nhỏ, điểm xuyết lên trên làm tăng độ giòn mát và cân bằng vị mặn ngọt. Đậu bo xanh mướt cùng cà rốt cam rực rỡ càng khiến món ăn sinh động, vừa là nguồn chất xơ tươi ngon, vừa làm nổi bật màu sắc hài hòa. Tàu hũ ky dai mềm thấm đẫm chút umami, đem lại kết cấu phong phú khi kết hợp với cơm chiên. Khi thưởng thức, bạn sẽ cảm nhận vị mặn ngọt đậm đà của tương đậu lên men, hòa quyện cùng sự tươi mát của rau củ và độ giòn mềm xen kẽ, tạo nên trải nghiệm ấm áp nhưng không kém phần tinh tế cho bữa ăn gia đình.', 'COMCKM.png'),
(52, 'MC', 'CƠM BÁT BỬU', 55000, 50, 'Cơm bát bửu (Eight Treasure Rice) là sự hòa quyện tinh tế giữa hạt gạo nếp dẻo mềm và hỗn hợp các loại hạt đậu, hạt sen, hạt sen khô, hạt dẻ, hạt điều, hạt bí, nho khô và lạc rang, tạo nên màu sắc rực rỡ và kết cấu phong phú. Khi chín, hạt gạo nở mềm, thơm mùi cốm dẻo, xen lẫn độ bùi bùi của hạt sen và hạt dẻ, vị ngọt nhẹ của nho khô cùng độ béo thanh của hạt điều và hạt bí. Sự kết hợp này không chỉ mang lại vẻ ngoài bắt mắt mà còn đem đến trải nghiệm đa tầng khẩu vị: dẻo, bùi, ngọt và hơi giòn. Món cơm tám bảo thường được thưởng thức vào những dịp sum họp, vừa thanh đạm nhưng vẫn đầy đủ dinh dưỡng, mang ý nghĩa may mắn, sung túc cho gia chủ.', 'COMBB.png'),
(53, 'MC', 'CƠM CHIÊN NẤM X.O', 95000, 50, 'Cơm chiên sốt nấm X.O mang đến hình ảnh rực rỡ với từng hạt cơm vàng óng, tơi mềm nhưng vẫn giữ độ đàn hồi. Nấm mỡ được xắt lát dày, xào sơ cho săn lại rồi quyện cùng sốt nấm X.O đặc sánh, có vị umami đậm đà, hơi cay nồng và mùi hương nấm thơm thoang thoảng. Những sợi tàu hũ ky mềm mại, dai vừa phải, thấm đẫm vị sốt, tạo nên kết cấu đa dạng khi cắn. Các nốt màu nâu nhạt của nấm mỡ, màu vàng nhạt của tàu hũ ky và sắc xanh nhạt của hành lá điểm xuyết làm món ăn thêm phần bắt mắt. Khi thưởng thức, bạn sẽ cảm nhận vị mặn ngọt hài hòa của sốt X.O, vị đất nhẹ nhàng của nấm mỡ và độ dai mềm đặc trưng của tàu hũ ky, quyện với cơm dẻo thơm. Tổng thể mang đến trải nghiệm đậm đà, tinh tế và rất hợp với những bữa cơm tối muốn đổi vị.', 'COMCXO.png'),
(54, 'MC', 'MÌ XÀO GIÒN', 85000, 50, 'Mì vàng xào rau củ mang đến hình ảnh bắt mắt với sợi mì vàng óng, mềm mại kết hợp cùng sắc cam rực rỡ của cà rốt thái sợi, sắc xanh tươi mát của đậu Hà Lan và rau cải, xen lẫn những miếng cà chua đỏ mọng nước và nấm đa dạng. Khi thưởng thức, bạn sẽ cảm nhận độ giòn nhẹ của đậu Hà Lan, vị ngọt thanh của cà rốt, độ mọng dịu của cà chua, cùng độ sần sật, umami tự nhiên từ các loại nấm như nấm mỡ, nấm hương hay nấm đông cô. Hương thơm dịu nhẹ của tỏi phi và hành lá điểm xuyết trên đỉnh tạo thêm chiều sâu cho món ăn. Tổng thể là sự hài hòa giữa các sắc màu và kết cấu, mang lại trải nghiệm tươi ngon, giàu dinh dưỡng và rất thanh đạm.', 'MIXG.png'),
(55, 'MC', 'MÌ TRƯỜNG THỌ TƯƠNG MÈ - NẤM THÔNG', 110000, 60, 'Mì trường thọ xóc sốt tương mè và nấm thông mang đến hình ảnh bắt mắt với sợi mì vàng óng, mềm mượt tự nhiên. Trên bề mặt, dưa leo xanh mát được cắt lát mỏng, kết hợp cùng giá đỗ trắng giòn tươi, tạo nên sự tươi mới và độ giòn đặc trưng. Sốt tương mè sánh mịn phủ đều từng sợi mì, mang vị béo ngậy, thơm thơm của mè rang, xen lẫn chút vị mặn dịu. Nấm thông tươi, thái lát mỏng, góp phần vị umami nhẹ nhàng, hơi đất đặc trưng của nấm porcini. Điểm xuyết trên cùng là những hạt thông giòn bùi, tỏa hương thơm thoảng khi nhai. Khi thưởng thức, bạn sẽ cảm nhận được sự hài hòa giữa độ dai mềm của mì, độ giòn tươi của rau, vị béo thơm của sốt và hương nấm ấm áp, tạo nên trải nghiệm thanh đạm nhưng vẫn rất đậm đà và đầy đủ dinh dưỡng.', 'MITT.png'),
(56, 'MC', 'MÌ XÀO THẬP CẨM', 85000, 50, 'Mì xào rau củ hiện lên bắt mắt với sợi mì săn chắc, vàng ươm, vừa dai vừa mềm. Từng sợi mì hòa quyện cùng sắc cam tươi của cà rốt thái sợi, những chấm xanh mướt của đậu Hà Lan và rau cải, xen lẫn miếng cà chua đỏ mọng nước. Các loại nấm như nấm mỡ, nấm hương hay nấm đông cô được xé hoặc cắt lát, cho vị umami tự nhiên và độ sần sật khi nhai. Hương tỏi phi nhẹ nhàng và chút hành lá cắt khúc nhỏ điểm xuyết trên mặt, tạo nên làn khói thơm phức khó cưỡng. Khi thưởng thức, bạn sẽ cảm nhận độ giòn tươi của rau củ, vị ngọt dịu của cà rốt và đậu Hà Lan, cùng độ mọng mềm của cà chua, tất cả kết hợp với sợi mì quyện gia vị đậm đà, mang đến bữa ăn vừa thanh đạm lại rất đầm vị.', 'MIXTC.png'),
(57, 'MC', 'HỦ TIẾU XÀO NẤM HONG KONG', 93000, 45, 'Món hủ tíu xào nấm hiện lên đầy hấp dẫn với sợi hủ tíu to bản, trắng trong, hơi dày dặn và mềm mại. Khi xào, hủ tíu giữ được độ dai vừa phải, quyện cùng vị ngọt tự nhiên của các loại nấm như nấm mỡ, nấm hương và nấm đông cô được cắt lát mỏng, tạo độ sần sật và hương umami đặc trưng. Giá đỗ trắng mập mạp điểm xuyết khắp đĩa, mang đến độ giòn tươi mát, cân bằng hoàn hảo với vị mềm của hủ tíu và độ béo nhẹ của nấm. Màu sắc của nấm nâu nhạt, giá trắng và hủ tíu vàng ánh được điểm tô thêm chút xanh của hành lá tạo thành bức tranh sinh động, bắt mắt. Mùi thơm nhẹ nhàng của tỏi phi và dầu hào lan tỏa khi vừa bưng ra, khiến người thưởng thức ngay lập tức muốn nhón đũa. Khi cắn miếng đầu tiên, bạn sẽ cảm nhận được sự hòa quyện hài hòa giữa vị ngọt, béo và chút cay nồng khẽ của gia vị, mang đến cảm giác ấm áp và ngon miệng cho bữa ăn.', 'HUTIEUXNHK.png'),
(58, 'MC', 'HỦ TIẾU ÁP CHẢO (GIÒN)', 92000, 45, 'Bánh hủ tiếu chiên giòn tan hiện lên với sắc vàng ruộm hấp dẫn, mỗi sợi bánh được ép mỏng rồi chiên nhanh trên chảo dầu, tạo lớp vỏ bên ngoài giòn rụm nhưng bên trong vẫn giữ độ mềm mượt. Cà rốt thái sợi mảnh, đậu Hà Lan và rau cải xanh mướt vừa chín tới giúp món ăn thêm tươi tắn, cân bằng hương vị. Những miếng cà chua đỏ mọng điểm xuyết tươi mới, thêm vị chua dịu khi vừa chín tới. Hương umami nhẹ nhàng của các loại nấm (nấm mỡ, nấm đùi gà, nấm hương) mang đến độ sần sật, sâu vị, khi kết hợp cùng lớp bánh giòn sẽ tạo nên trải nghiệm thú vị: bên ngoài giòn tan, bên trong xen lẫn độ tươi ngọt của rau củ và vị đậm đà của nấm. Tổng thể món ăn không chỉ bắt mắt mà còn hài hòa giữa độ giòn, độ ngọt thanh và đậm đà, khiến bữa cơm thêm phần sinh động.', 'HUTIEUAC.png'),
(59, 'MC', 'MIẾN XÀO BROCCOLI', 75000, 55, 'Miến đậu xanh mềm mượt, trắng trong khi kết hợp cùng nhiều loại rau củ và nấm tạo nên món ăn vừa thanh đạm vừa đầy dinh dưỡng. Những sợi miến sau khi chần vừa tới giữ được độ dai, hấp thụ tinh túy từ nước dùng thanh ngọt. Rau cải xanh mướt, điểm thêm sắc vàng tươi của cà rốt thái sợi và đậu Hà Lan giòn ngọt, khiến tổng thể món ăn thêm sinh động. Các loại nấm như nấm đông cô, nấm mỡ và nấm đùi gà mang đến vị umami tự nhiên, độ sần sật thú vị, đồng thời lan tỏa hương đất nhẹ nhàng. Khi thưởng thức, từng miếng miến quện cùng mùi thơm dịu của tỏi phi và chút nước tương chay, khiến vị thanh của rau củ, vị bùi mềm của miến và vị đậm đà từ nấm hài hòa tuyệt vời trong miệng.', 'MIENX.png'),
(60, 'MC', 'MIẾN TAY CẦM', 65000, 50, 'Miến đậu xanh được hầm trong nồi đất vừa giữ độ ấm lâu vừa lan tỏa hương vị quyện chặt, tạo nên độ mềm mượt đặc trưng. Nấm rơm cắt miếng vừa ăn, khi nấu thấm đượm vị ngọt tự nhiên, vẹn nguyên độ sần sật; nấm linh chi bổ sung chiều sâu umami, nhẹ mùi đất và hơi đăng đắng đặc trưng, càng làm bữa ăn thêm phần tinh tế. Ngũ vị hương hòa quyện cùng nước hầm, tỏa mùi thơm ấm áp, thoảng hương quế, hồi, đinh hương, ngọt dịu của hoa hồi, tạo lớp gia vị đằm thắm len lỏi trong từng sợi miến. Màu sắc của miến trắng ngà xen lẫn sắc nâu nhạt của nấm và chút gạch ngũ vị hương điểm xuyết khiến món ăn thêm phần hấp dẫn. Khi múc ra bát, hơi nghi ngút bốc lên, vị ngọt thanh của miến hòa cùng vị đậm đà, ấm nồng của ngũ vị và hương nấm, cho cảm giác ấm áp, giòn mềm, rất thích hợp cho ngày mưa se lạnh hoặc bữa cơm chiều gia đình.', 'MIENTC.png');
INSERT INTO `items` (`id`, `TT`, `name`, `price`, `quantity`, `description`, `image_url`) VALUES
(61, 'MC', 'CÀ RI VÀNG', 85000, 60, 'Cà ri vàng với khoai lang và nấm tạo nên sắc vàng ươm ấm áp, kết hợp cùng vị ngọt dịu của khoai và hương umami nhẹ nhàng của nấm. Nước cà ri sánh mịn, pha chút vị cay nhẹ và mùi hương gia vị phương Đông, khi chan lên cơm hoặc bún tươi sẽ thấm đẫm vào từng hạt, tạo cảm giác ấm bụng và đầy đủ hương vị. Miếng khoai lang mềm béo, ngọt thanh hài hòa với độ dai giòn dai của nấm, khiến mỗi muỗng cà ri đều đậm đà đa tầng. Điểm nhấn thú vị là những miếng hoành thánh chiên giòn tan, khi nhúng vào sốt cà ri sẽ giữ được độ giòn nhẹ, đồng thời thấm chút béo ngậy khiến món ăn càng thêm hấp dẫn. Nếu dùng kèm bánh mì baguette, phần vỏ giòn giòn sẽ là “vũ khí” lý tưởng để quết trọn vẹn hương vị cà ri vàng đậm đà. Mỗi lựa chọn—cơm, bún hoặc bánh mì—đều mang đến trải nghiệm khác biệt, nhưng đều chung một điểm nhấn là nước cà ri thơm lừng, vị ngọt thanh của khoai lang và độ sần sật của nấm, khiến món ăn rất lôi cuốn.', 'CARIV.png'),
(62, 'MC', 'MÌ XÀO NẤM X.O', 105000, 50, 'Mì vàng xào sốt nấm X.O hiện lên với sợi mì óng ả, săn chắc nhưng vẫn giữ độ mềm mại khi nhai. Sốt nấm X.O sánh mịn, ngấm đều vào từng sợi mì, mang vị umami đậm đà cùng chút cay nồng đặc trưng. Nấm mỡ được xắt lát dày, hơi săn và thấm đẫm sốt, tạo nên kết cấu sần sật mỗi khi cắn. Bông hẹ xanh mướt rải đều khắp đĩa, tỏa hương thơm dịu nhẹ, hòa quyện cùng vị tươi mát của giá đỗ giòn tan bên dưới, làm cân bằng độ béo ngậy của sốt. Mỗi miếng mì khi ăn kèm với nấm, bông hẹ và giá đỗ đều mang đến sự hài hòa giữa hương vị đậm đà, độ giòn tươi và mùi thơm tinh tế, khiến người thưởng thức vừa cảm nhận được chiều sâu của nước sốt X.O, vừa thấy bữa ăn thật sinh động, tròn vị.', 'MIXNXO.png'),
(63, 'C', 'CANH MƯỚP MỒNG TƠI', 75000, 60, 'Canh mướp mùng tơi nấm mối hiện lên với màu xanh mướt của mướp non và mùng tơi, điểm xuyết những viên nấm mối vàng nâu óng ánh. Khi múc ra bát, nước canh trong veo, phảng phất mùi ngọt dịu tự nhiên từ nấm mối, hương thanh mát của mướp và mùng tơi. Sợi mướp mềm mại, ngấm đẫm vị ngọt của nước, còn mùng tơi mang độ mềm mượt và hơi nhớt đặc trưng, giúp nước canh thêm sánh và đầm. Nấm mối được xé nhỏ hoặc để nguyên từng cây nhỏ, cho vị giòn dai, thơm đất nhẹ nhàng, làm tăng chiều sâu hương vị. Khi thưởng thức, từng muỗng canh mang đến cảm giác thanh nhẹ, vị ngọt tự nhiên và chút béo bùi tiềm ẩn ở nấm mối, tạo nên món ăn vừa đơn giản lại rất đậm đà, hoàn hảo cho những ngày muốn giải nhiệt hoặc bữa cơm gia đình thêm phần chất lượng và bổ dưỡng.', 'CANHMMT.png'),
(64, 'C', 'CANH CHUA MIỀN TÂY', 89000, 60, 'Canh chua miền Tây hiện lên với màu sắc tươi tắn: nước canh trong veo điểm xuyết sắc đỏ tươi của cà chua chín mọng, xen lẫn sắc xanh đậm của đậu bắp và lá bạc hà non. Những cây nấm rơm vàng nhẹ nổi trên mặt, kèm theo giá đỗ trắng ngần tạo độ giòn tươi mát. Mùi thơm tổng hòa của tỏi phi vàng ươm lẫn chút hăng dịu của bạc hà và vị chua thanh thanh từ cà chua, khi chan với nước hầm rau củ ngọt dịu làm nổi bật hương vị nhẹ nhàng mà đầm đà. Khi thưởng thức, từng miếng đậu bắp mềm ngọt, giá giòn sần sật, nấm rơm dai dai, kết hợp cùng lá bạc hà thêm phần tươi mát. Mỗi muỗng canh đem đến cảm giác giải nhiệt, thanh nhẹ nhưng vẫn đầy đủ hương vị tinh túy của ẩm thực Nam Bộ.', 'CANHCMT.png'),
(65, 'C', 'CANH RONG BIỂN', 84000, 50, 'Canh rong biển đậu hũ hiện lên với sắc xanh đậm của rong biển tươi, điểm thêm sắc trắng tinh khôi của đậu hũ mềm mại. Nấm các loại như nấm hương, nấm rơm và nấm đùi gà được cắt miếng vừa ăn, mang đến độ giòn sần sật và vị umami tự nhiên đậm đà. Nước hầm rau củ trong veo, phảng phất mùi ngọt dịu của cà rốt, cải xoong và hành tây, khi kết hợp cùng rong biển và nấm tạo nên hương vị thanh nhẹ nhưng vẫn sâu đậm. Khi thưởng thức, bạn sẽ cảm nhận độ mềm mịn của đậu hũ, vị sần sật của nấm, hòa quyện cùng độ nhớt nhẹ nhàng của rong biển. Từng thìa canh lan tỏa cảm giác thanh mát, rất phù hợp cho những ngày muốn ăn nhẹ nhưng vẫn đảm bảo đầy đủ chất dinh dưỡng.', 'CANHRB.png'),
(66, 'C', 'CANH BÔNG HẸ', 65000, 50, 'Canh bông hẹ với đậu hũ vàng và nấm mang đến vẻ thanh mát từ sắc xanh nhạt của bông hẹ, điểm thêm sắc vàng óng của miếng đậu hũ chiên nhẹ. Những cụm bông hẹ mảnh mai, khi nấu chỉ hơi tái giữ được độ mềm và hương hăng nhẹ đặc trưng, lan toả khắp bát canh. Đậu hũ vàng bên ngoài giòn nhẹ, bên trong mềm mịn, thấm rõ vị ngọt dịu của nước dùng; nấm như nấm hương, nấm rơm hay nấm đùi gà được cắt miếng vừa ăn, khi chín cho độ sần sật và vị umami đằm thắm, làm dày thêm chiều sâu hương vị. Nước hầm rau củ trong veo, hòa quyện cùng vị thơm ngọt tự nhiên của cà rốt, hành tây và chút muối gom đủ vị; khi chan vào bát, hương tỏi phi vàng giòn khiến mâm cơm thêm phần ấm áp. Mỗi muỗng canh đem đến trải nghiệm dịu dàng: độ mềm của đậu hũ, vị sần của nấm và hương thanh của bông hẹ hòa quyện, tạo nên món canh nhẹ nhàng mà đậm đà, rất thích hợp cho những ngày muốn ăn thanh đạm nhưng vẫn đủ dưỡng chất.', 'CANHBH.png'),
(67, 'C', 'CANH HẦM NẤM MỐI ', 110000, 50, 'Canh nấm mối hầm cải khô và các loại hạt/quả toát lên vẻ ấm áp từ sắc nâu nhạt của nấm mối cùng sắc xanh đậm của cải khô đã ngả màu vàng. Nấm mối dai giòn, thấm đượm vị ngọt thanh của nước hầm rau củ trong veo, kết hợp cùng cải khô mềm mượt và thoảng mùi chua nhẹ, mang lại chiều sâu hấp dẫn. Các loại quả, hạt như hạt sen bùi bùi, hạt sen khô mềm dẻo, hạt điều giòn bùi và đậu xanh tách vỏ tan nhẹ càng làm bát canh thêm phần phong phú và đa dạng kết cấu. Khi múc lên, từng muỗng nước canh lan tỏa hương thơm dịu của rau củ hầm, hòa cùng vị umami nhẹ nhàng của nấm, kết thúc bằng hậu vị bùi thanh của hạt, tạo nên trải nghiệm đậm đà mà thanh khiết, rất thích hợp cho những ngày tiết trời se lạnh hoặc bữa cơm gia đình muốn tận hưởng nét tinh tế, bổ dưỡng.', 'CANHNM.png'),
(68, 'C', 'CANH CẢI CHUA TỨ XUYÊN', 77000, 50, 'Canh cải chua kiểu Tứ Xuyên hiện lên với nước dùng trong veo, điểm những mảng đỏ tươi của cà chua chín mọng và sắc xanh vàng của cải chua lên men. Khi múc, khói bốc nghi ngút mang theo hương thơm đặc trưng của xuyên tiêu – nồng nàn, hơi tê đầu lưỡi, gợi cảm giác ấm áp. Những lát nấm các loại vươn lên mềm mại, thấm đượm vị chua thanh của nước hầm rau củ, kết hợp cùng xác cải chua giòn giòn nhưng đã mềm hơn do được hầm, tạo độ chua dịu êm chứ không gắt. Đậu hũ trắng ngà, cắt khối nhỏ, nổi bật giữa bát, ngoài lớp vỏ mịn màng bên ngoài là phần thịt đậu mềm thấm gia vị, mang đến nét béo nhẹ nhàng, bù trừ cho vị chua cay. Khi thưởng thức, từng muỗng canh lan tỏa vị chua thanh, vị ngọt dịu của nấm và cà chua, chấm chút tê tê của xuyên tiêu ở cuối cuống họng, khiến bữa ăn trở nên sinh động, kích thích vị giác. Món canh này đặc biệt tạo cảm giác giải nhiệt nhưng vẫn đủ độ ấm nồng, rất hợp cho những ngày thời tiết se lạnh hoặc khi cần đổi vị cho bữa cơm gia đình.', 'CANHCTX.png'),
(69, 'L', 'LẨU CHAO ĐỎ', 310000, 50, 'Lẩu chao đỏ toát lên sắc vàng cam rực rỡ, mang hương vị đậm đà từ chao đỏ. Nước lẩu sánh mịn, hơi sệt, phảng phất mùi béo ngậy và vị chua thanh nhẹ đặc trưng của chao lên men, kích thích vị giác ngay khi vừa ngửi. Khoai môn được cắt miếng vuông vừa ăn, khi nhúng vào lẩu sẽ mềm bùi, ngấm trọn hương chao, tạo độ giòn tan nhẹ dưới răng. Tàu hũ ky cuộn nấm được xếp gọn gàng, khi chín sẽ thấm đượm nước lẩu đậm đà, nấm bên trong giữ vị sần sật, bùi bùi, tạo điểm nhấn thú vị giữa lớp vỏ đậu mịn màng. Khi ăn, chỉ cần nhúng các loại rau xanh như cải ngọt, cải thảo, rau muống và nấm đa dạng (nấm rơm, nấm hương, nấm đùi gà) vào nước lẩu, chúng nhanh chóng hút vị chao cay nhẹ và ngọt thanh, mang đến cảm giác tươi mát nhưng không kém phần đậm đà. Từng miếng rau giòn tươi xen lẫn nấm sần sật hòa quyện cùng khoai môn bùi béo và tàu hũ ky dai mềm, khiến bữa lẩu trở nên phong phú hơn. Mỗi lần nhúng – vớt, hơi khói nghi ngút mang theo hương chao đỏ lan tỏa, khiến người thưởng thức cảm nhận trọn vẹn cái ấm áp, sảng khoái của lẩu chay, rất thích hợp cho những buổi sum họp gia đình hay bạn bè trong ngày se lạnh.', 'LAUCD.png'),
(70, 'L', 'LẨU NẤM THẬP CẨM', 325000, 50, 'Lẩu nấm rong biển toát lên sắc xanh đậm rì của rong biển kết hợp cùng những lát táo đỏ điểm xuyết như ngọc. Nước lẩu trong veo, phảng phất mùi thơm dịu của táo đỏ và vị ngọt thanh tự nhiên từ rong biển. Đậu hũ trắng ngà, cắt khối vuông, khi chín giữ được độ mềm mịn, thấm đượm tinh túy của nước lẩu. Khi nhúng vào, các loại rau xanh như cải ngọt, cải thảo, rau muống tạo độ giòn tươi mát, còn nấm rơm, nấm hương, nấm đùi gà mang vị umami đằm thắm và kết cấu sần sật. Từng miếng rau, nấm, đậu hũ khi chạm vào nước lẩu sẽ hút trọn vị ngọt, vị chua nhẹ của táo đỏ cùng chút vị biển mằn mặn của rong biển. Mỗi bữa lẩu, khói nghi ngút bốc lên quyện hương nồng nàn, đem lại cảm giác ấm áp, thanh nhẹ và rất bổ dưỡng.', 'LAUNTC.png'),
(71, 'L', 'LẨU TRƯỜNG THỌ', 345000, 50, 'Lẩu nấm sữa hạnh nhân hiện lên với nước dùng màu trắng ngà, bóng bẩy, phảng phất hương thơm ngọt dịu của sữa hạnh nhân kết hợp cùng mùi nồng nàn nhẹ nhàng từ táo đỏ. Khi bưng nồi lẩu lên, khói tỏa lan mang theo mùi ngọt ấm, gợi cảm giác thanh nhẹ nhưng vẫn đầy đủ vị đậm đà. Nấm trùng thảo vàng ươm, mềm dai, khi thả vào lẩu thấm đượm tinh túy của sữa và táo, mang đến vị bùi bùi, hơi bột bột đặc trưng. Khi nhúng rau và các loại nấm khác như nấm đông cô, nấm rơm hoặc rau cải xanh, bạn lập tức cảm nhận được độ giòn tươi của rau hòa cùng vị umami nhẹ nhàng của nấm, làm nền cho hậu vị béo ngậy, ngọt thanh. Từng miếng rau, nấm, khi ngấm đủ nước lẩu sữa hạnh nhân sẽ mang đến trải nghiệm nhiều tầng hương: ngoài mềm mượt, trong ngọt dịu, xen lẫn chút hương thảo mộc của táo đỏ và vị ấm của sữa hạnh nhân, khiến mỗi lần thưởng thức đều rất ấm áp và bổ dưỡng.', 'LAUTT.png'),
(72, 'L', 'LẨU CHUA CAY', 315000, 50, 'Canh lẩu chua cay hiện lên với nước dùng trong veo ánh đỏ nhạt, hòa quyện vị chua nhẹ của me và hương ấm của ớt sừng. Lá chanh thái sợi điểm xuyết trên mặt nước, tỏa hương cam quýt thoang thoảng. Đậu hũ trắng ngà cắt khối vuông, khi chín thấm đượm vị chua cay, tạo độ béo nhẹ cân bằng.\r\n\r\nKhi nhúng rau cải thảo, cải ngọt, rau muống và các loại nấm như nấm đùi gà, nấm rơm, từng miếng nhanh chóng giữ độ giòn tươi, thấm trọn vị chua cay ấm nồng. Mỗi thìa nước lẩu chan vào bát thêm ít hành ngò và tiêu xanh, dư vị chua cay kích thích lan tỏa trong khoang miệng, khiến bữa lẩu chay vừa thanh mát vừa đậm đà, rất hợp cho ngày se lạnh.', 'LAUCC.png'),
(73, 'TB', 'BÁNH MÌ NƯỚNG HẢI NAM', 45000, 100, 'Bánh mì nướng Hải Nam tại Chợ Lớn thường được làm từ những ổ bánh mì đặc ruột, vỏ giòn rụm, cắt ngang và nướng trên than hồng cho đến khi bề mặt cháy xém nhẹ, tỏa hương thơm thoang thoảng. Lớp nhân bên trong được phết một lớp bơ nhạt cùng sốt đặc trưng hòa quyện vị mằn mặn, béo ngậy, đôi khi có thêm chút tương ớt hoặc tương đậu chua dịu để tăng độ đậm đà. Khi ăn, miếng bánh mì giòn tan ở ngoài nhưng vẫn giữ độ mềm mại bên trong, hương vị bơ và sốt làm nổi bật mùi bánh thơm nướng, tạo cảm giác vừa quen thuộc vừa mới lạ.', 'BANHMNHN.png'),
(74, 'TB', 'DƯƠNG CHI CAM LỘ', 43000, 100, 'Lớp sữa xoài béo thơm, từng miếng xòai tươi ngọt ngào, cắt chút tép bưởi chua chua, hài hoà sau một bữa chay thanh tao quả là thú thưởng thức tuyệt vời.\r\nQuả thật, sự hòa quyện giữa lớp sữa xoài béo ngậy và miếng xoài tươi ngọt mát tạo nên cảm giác vừa cuốn hút vừa thanh khiết. Khi xen lẫn chút tép bưởi chua dịu, món tráng miệng ấy bừng lên hương vị sảng khoái, khiến mỗi muỗng đều mang đến sự cân bằng tuyệt diệu. Sau một bữa chay thanh tao, ly xoài kết hợp bưởi như thế thực sự là “liều thuốc” hoàn hảo để làm dịu vị giác, đem lại cảm giác thỏa mãn mà không quá ngấy. Hương thơm quyến rũ của xoài, vị chua nhẹ của bưởi cùng độ béo mượt của sữa đánh tan mọi căng thẳng, giúp tâm hồn nhẹ nhàng, tràn đầy năng lượng. Đây đúng là thú vui đơn giản nhưng đậm đà, hoàn hảo cho những khoảnh khắc thư giãn sau bữa chay.', 'DCCL.png'),
(75, 'TB', 'SỮA CHUA NẾP CẨM', 28000, 150, 'Sữa chua nếp cẩm là sự hòa quyện tinh tế giữa vị béo mượt của sữa chua và độ dẻo thơm của nếp cẩm tím nguyên chất. Từng muỗng nếp cẩm dẻo quánh, đượm vị ngọt tự nhiên, ôm lấy vị chua nhẹ dịu của sữa, tạo nên một cảm giác cân bằng hoàn hảo. Màu tím nhạt của nếp cẩm xen giữa lớp sữa trắng ngà vừa bắt mắt vừa gợi cảm giác nhà quê ấm áp. Khi ăn, sự bùi bùi từ hạt nếp cẩm, quyện cùng độ mịn của sữa chua, len lỏi vào từng ngóc ngách vị giác, khiến mỗi miếng vừa thanh vừa đượm, khó lòng cưỡng lại. Món này không chỉ ngon miệng mà còn mang ý nghĩa may mắn, sum vầy khi đem lên mâm trong những dịp Tết; mỗi vị ngọt dịu đều như một lời chúc phúc cho năm mới an khang, hạnh phúc và gắn kết gia đình.', 'SCNC.png'),
(76, 'TB', 'NƯỚC MÓT', 45000, 100, 'Nước mót là món nước được làm từ các loại thảo mộc thiên nhiên rất nổi tiếng ở Hội An, được mệnh danh là thức uống không thể bỏ lỡ của hầu hết du khách đến với phố cổ. Nước mót được nấu từ nhiều loại thảo mộc như la hán quả, cam thảo, kim ngân hoa, hạ khô thảo, lá trà xanh, hoa cúc, lá sen khô, sả, mật ong, đường,...\r\nMón nước có hương vị thanh mát, có tác dụng thanh lọc cho cơ thể, rất phù hợp với những ngày nắng nóng nên có thể giúp người uống xua tan cơn khát tức thời. Nước mót mang hương vị dân dã nhưng lại đặc biệt đến khó quên, có lẽ đây là lý do vì sao thức uống đơn giản này lại trở thành thức uống không thể bỏ lỡ khi đặt chân đến Hội An - góc phố cổ yên ắng mà cũng xô bồ náo nhiệt.', 'NUOCMOT.png\r\n'),
(77, 'TB', 'TRÀ CHANH DÂY MĂNG CỤT', 55000, 100, 'Trà chanh dây măng cụt hiện lên với lớp nước trong vắt pha chút hồng nhạt ánh tím, gợi nhớ hình ảnh quả chanh dây chín mọng và vỏ măng cụt sậm màu. Khi rót ra ly, hương chanh dây tươi mát, hơi chua nhẹ lan tỏa trước, rồi đọng lại vị ngọt thanh của măng cụt nơi đầu lưỡi. Từng giọt trà xanh dịu êm ôm lấy hương trái cây nhiệt đới, tạo thêm độ sâu cho mỗi ngụm uống. Những hạt chanh dây nhỏ li ti điểm xuyến như ngọc trai vàng, lắc nhẹ dưới đá viên, phản chiếu ánh sáng lấp lánh. Vị chua thanh của chanh dây hòa cùng vị ngọt bùi dịu của măng cụt, xen lẫn hậu vị trà thoang thoảng, khiến cảm giác tươi mát kéo dài suốt buổi chiều. Khi nhấp ngụm cuối, dư vị chua – ngọt cân bằng, vừa tỉnh táo vừa thanh mát, là lựa chọn lý tưởng để giải khát, xua tan oi bức và gợi nhớ hương vị nhiệt đới trọn vẹn.', 'TRACDMC.png'),
(78, 'TB', 'TRÀ ATISO ĐỎ', 30000, 150, 'Trà atiso đỏ có màu hồng thẫm ấm áp ngay từ khi hãm, ánh lên nét rực rỡ như ánh mặt trời cuối chiều. Hương thơm của nó thoang thoảng mùi đất ấm, lẫn chút hương hoa cỏ dịu nhẹ, cắn vào ngụm đầu tiên, bạn sẽ cảm nhận vị chua thanh mát, hơi se đầu lưỡi và hậu ngọt nhẹ nhàng lưu lại kéo dài. Đặc biệt, vị chua của atiso đỏ không gắt như chanh mà dịu dàng, cùng độ sánh vừa phải tạo nên cảm giác mượt mà khi trôi qua cổ. Khi nhấp tiếp, bạn còn cảm thấy chút đắng nhẹ ở vòm họng, giống như dư vị của trà thảo mộc thuần khiết. Tóm lại, trà atiso đỏ hội tụ hương vị chua ngọt hài hòa, giúp giải nhiệt, thanh lọc cơ thể và mang lại cảm giác thư thái đậm chất thiên nhiên mỗi khi thưởng thức.', 'TRAATS.png'),
(79, 'TG', 'GỎI CỦ HŨ DỪA', 78000, 60, 'Gỏi củ hũ dừa – nấm bào ngư mang lại “combo” dưỡng chất nhẹ bụng mà giàu năng lượng: chất xơ hòa tan trong củ hũ dừa hỗ trợ tiêu hóa trơn tru, ngừa táo bón; nấm bào ngư xé sợi cung cấp beta-glucan và acid amin thiết yếu giúp tăng cường sức khỏe, duy trì khối cơ; đậu phộng rang bùi béo bổ sung protein thực vật cùng vitamin E và chất béo không bão hòa, có lợi cho tim mạch; rau mùi thêm tinh dầu chống ô-xy hóa, hỗ trợ giải độc gan và làm dậy hương. Kết hợp lại, món gỏi vừa thanh mát giòn sật, vừa giúp ổn định đường huyết, cung cấp năng lượng bền, phù hợp thực đơn eat-clean hay bữa khai vị chay lành mạnh.', 'GOICHD.png'),
(80, 'RCQ', 'RAU HẤP THẬP CẨM & KHO QUẸT', 85000, 100, 'Các loại rau củ như bông cải xanh, cà rốt, đậu que, bắp cải tím và su hào được hấp trong xửng đến khi vừa chín tới, vẫn giữ độ giòn và màu sắc tươi tắn. Kho quẹt chay làm từ đường thốt nốt thắng chín đến khi chuyển sang màu nâu cánh gián, sau đó thêm nước tương, nước dừa tươi, tỏi băm và nấm mèo xay nhỏ. Nấm mèo giúp tạo vị umami đậm đà thay thế cho tôm khô, còn nước dừa và đường thốt nốt mang đến độ ngọt dịu, hơi béo, sánh mượt. Khi chấm rau, bạn sẽ cảm nhận vị ngọt thanh của rau hấp hòa cùng vị mặn ngọt, mùi thơm của tỏi phi và nấm mèo. Món chay này không chỉ đảm bảo dinh dưỡng, màu sắc bắt mắt mà còn mang đến hương vị hài hòa, thích hợp cho bữa ăn gia đình hoặc tiệc chay nhẹ nhàng.', 'RAUHTC.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `points_history`
--

CREATE TABLE `points_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Tham chiếu users.id',
  `change_amount` int(11) NOT NULL COMMENT 'Số điểm cộng (+) hoặc trừ (–)',
  `type` enum('earn','redeem') NOT NULL COMMENT 'Loại thay đổi',
  `reference_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Ví dụ: order_id, voucher_id,…',
  `description` varchar(255) DEFAULT NULL COMMENT 'Mô tả thêm',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `profiles`
--

CREATE TABLE `profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `profiles`
--

INSERT INTO `profiles` (`user_id`, `fullname`, `gender`, `dob`, `avatar`, `points`) VALUES
(2, 'Cốc Thiệu Thanh', 'male', '2000-06-08', 'uploads/avatars/avt_2_1751303678.jpg', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `points_total` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `points_total`) VALUES
(1, 'Niên Cốc', 'dangthixuan08062004@gmai.com', '$2y$10$CK8X8W7d5EmEMfbstOCFSOOSASd57KxmanmoDlx2Y.j6JmKrhDwPO', '2025-06-29 14:28:09', 0),
(2, 'cb86', 'dangthixuan2272004@gmai.com', '$2y$10$Vhzvp0t4t25LVzRl5olTDe2m8pn92yOHiRiklswNmKIe8/LP5r5mC', '2025-06-30 23:55:54', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = địa chỉ mặc định',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `fullname`, `phone`, `address`, `is_default`, `created_at`, `updated_at`) VALUES
(2, 2, 'Lan Nguyễn', '0333296470', '124, đường Thăng Long, Phường Gò Vấp, Quận Gò Vấp, Hồ Chí Minh', 1, '2025-07-01 03:13:03', '2025-07-08 15:41:00'),
(3, 2, 'Lannnn', '0933805504', '143/55, Lê Lợi, phường Hạnh Thông, Quận Gò Vấp, Hồ Chí Minh', 0, '2025-07-01 03:43:58', '2025-07-08 14:49:42'),
(4, 2, 'Niên Cốc', '0333278393', '123, Tên Lửa, Phường Trung Mỹ Tây, Quận 12, Hồ Chí Minh', 0, '2025-07-01 14:14:09', '2025-07-08 14:15:06'),
(5, 2, 'Bùi Kiên', '0333278393', '133/22, Lê Thúc Hoạch, Phường Tân Định, Quận 1, Hồ Chí Minh', 0, '2025-07-07 01:10:39', '2025-07-07 03:07:10'),
(6, 2, 'Võ Xuân Quỳnh', '0333296470', '133/22, Nguyễn Cư Trinh, Phường Tân Định, Quận 1, Hồ Chí Minh', 0, '2025-07-08 12:14:57', '2025-07-08 12:14:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT 'Mã voucher',
  `description` varchar(255) DEFAULT NULL COMMENT 'Mô tả',
  `points_required` int(11) NOT NULL COMMENT 'Số điểm cần để đổi',
  `discount_type` enum('amount','percent') NOT NULL DEFAULT 'amount',
  `discount_value` decimal(10,2) NOT NULL COMMENT 'Giá trị giảm (số tiền hoặc %)',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=đang mở,0=đóng',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime DEFAULT NULL COMMENT 'Ngày hết hạn (NULL = vô thời hạn)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `description`, `points_required`, `discount_type`, `discount_value`, `active`, `created_at`, `updated_at`, `expires_at`) VALUES
(1, 'BROC10K', 'Giảm 10.000₫ cho đơn hàng bất kỳ', 50, 'amount', 10000.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(2, 'BROC20K', 'Giảm 20.000₫ cho đơn hàng ≥ 200.000₫', 100, 'amount', 20000.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(3, 'BROC10P', 'Giảm 10% cho đơn hàng ≥ 100.000₫', 150, 'percent', 10.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(4, 'BROC15P', 'Giảm 15% cho đơn hàng ≥ 200.000₫', 200, 'percent', 15.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(5, 'BROC25P', 'Giảm 25% cho đơn hàng ≥ 300.000₫', 300, 'percent', 25.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(6, 'FREEDEL', 'Miễn phí giao hàng cho đơn ≥ 150.000₫', 80, 'amount', 30000.00, 1, '2025-07-01 01:46:38', '2025-07-01 01:46:38', NULL),
(7, 'NY2025', 'Giảm 20% Tết Dương lịch 2025', 0, 'percent', 20.00, 1, '2025-07-01 01:50:38', '2025-07-01 01:50:38', '2025-01-07 23:59:59'),
(8, 'LOVE14', 'Giảm 15% Valentine 14/2', 0, 'percent', 15.00, 1, '2025-07-01 01:50:38', '2025-07-01 01:50:38', '2025-02-15 23:59:59'),
(9, 'WOMEN8M', 'Giảm 20% 8/3 Quốc tế Phụ nữ', 0, 'percent', 20.00, 1, '2025-07-01 01:50:38', '2025-07-01 01:50:38', '2025-03-08 23:59:59'),
(10, 'MIDAUT', 'Giảm 30.000₫ Trung Thu', 0, 'amount', 30000.00, 1, '2025-07-01 01:50:38', '2025-07-01 01:50:38', '2025-09-30 23:59:59'),
(11, 'NEWBROC', 'Giảm 10% cho thành viên mới (hạn 30 ngày kể từ ngày tạo)', 0, 'percent', 10.00, 1, '2025-07-01 01:50:38', '2025-07-01 01:50:38', '2025-07-31 01:50:38');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_item` (`item_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`TT`);

--
-- Chỉ mục cho bảng `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `items_ibfk_1` (`TT`);

--
-- Chỉ mục cho bảng `points_history`
--
ALTER TABLE `points_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ph_user` (`user_id`);

--
-- Chỉ mục cho bảng `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_addresses_user` (`user_id`);

--
-- Chỉ mục cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `items`
--
ALTER TABLE `items`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT cho bảng `points_history`
--
ALTER TABLE `points_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`TT`) REFERENCES `categories` (`TT`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `points_history`
--
ALTER TABLE `points_history`
  ADD CONSTRAINT `fk_ph_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_profiles_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

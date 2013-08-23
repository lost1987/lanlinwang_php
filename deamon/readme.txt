| statistic | CREATE TABLE `statistic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param_nums` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL,
  `time` int(11) NOT NULL,
  `param_digits1` int(11) DEFAULT NULL,
  `param_digits2` int(11) DEFAULT NULL,
  `param_digits3` int(11) DEFAULT NULL,
  `param_digits4` int(11) DEFAULT NULL,
  `param_digits5` int(11) DEFAULT NULL,
  `param_digits6` int(11) DEFAULT NULL,
  `param_digits7` int(11) DEFAULT NULL,
  `param_digits8` int(11) DEFAULT NULL,
  `param_digits9` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`) USING BTREE,
  KEY `idx_time` (`time`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=377 DEFAULT CHARSET=utf8 |

param_nums 存放数量
param_digits 存放数值

type 为 1 : param_nums 存放的是 当前时间time 的登录在线的人数
type 为 2 : param_digits1 存放的是 当前时间用户所拥有的元宝总数
type 为 3 : param_digits1 存放的是 当前时间为止72小时内登录过用户的元宝剩余总量
type 为 4 : param_digits1 存放的是 当前时间为止24小时内登录过用户数 param_digits2 存放的是 当前时间为止72小时内登录过用户数
                param_digits3存放的是 当前时间为止168小时内登录过用户数

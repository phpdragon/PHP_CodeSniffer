#!/bin/bash

############################################
# 1.编辑代码库所在的[ 代码库/.hg/hgrc ]文件
# 2.添加如下节点
# [hooks]
# pretxnchangegroup = /home/hg/php_codesniffer/scripts/hg_pretxnchangegroup_hook.sh
#
# 3.赋予该脚本可执行权限 chmod a+x 
# 以上
############################################

echo -e "\n==========================Starting PHP Syntax Check==========================\n"

#在/tmp目录下创建临时目录
TEMP_DIR=`mktemp -dt php_syntax_files.XXXXXX`

#临时测试
#TEMP_DIR="/tmp/test/"
#HG_NODE="d0a1ccf22a26dfe62ae1db932dcc4972509b8f0b"

HG_BIN="/usr/bin/hg"
CHECK_CMD="/usr/bin/php /home/hg/php_codesniffer/scripts/phpcs --standard=Fenqile --tab-width=4 --extensions=php $TEMP_DIR"
FORCE_CHECK_CMD="/usr/bin/php /home/hg/php_codesniffer/scripts/phpcs --standard=FenqileForce --tab-width=4 --extensions=php $TEMP_DIR"

#输出临时目录
echo "temp dir : "$TEMP_DIR

#导出当前至最新
#hg archive -r $HG_NODE:tip -t files /tmp/test
#导出当前
#hg archive -r $HG_NODE -t files /tmp/test
#全量导出
#hg archive -r tip -t files /tmp/test
#导出修改部分的代码
echo $HG_BIN" archive -I \"set:added() or modified()\" -r "$HG_NODE":tip -t files "$TEMP_DIR
$HG_BIN archive -I "set:added() or modified()" -r $HG_NODE:tip -t files $TEMP_DIR

#检测代码,输出提示
echo "$CHECK_CMD"
$CHECK_CMD

echo -e "\n\n\n\n=========================Force Check PHP Code Syntax=========================\n"

#强制检测代码
echo "$FORCE_CHECK_CMD"
TEST_SYNTAX=`$FORCE_CHECK_CMD`
echo -e "$TEST_SYNTAX"

#删除目录
echo "rm -rf "$TEMP_DIR
rm -rf "$TEMP_DIR"

if [ "0" == "${TEST_SYNTAX}" ] || [ "" == "${TEST_SYNTAX}" ];then
    echo -e "\n\nThrough code detection, allowed to push! Push successfully!"
    echo -e "\n===============================================================================\n\n"
    exit 0
fi

echo -e "\n\nServer detected the code has a problem, please check to submit the push again!"
echo -e "\n===============================================================================\n\n"
exit 1

# 验签规则

#### 签名字段
    参与签名的字段包括account(账号)，created(时间戳)，nonce(随机字符串);

#### 签名规则
    1、对所有签名字段按照字段名的ASCII码从小到大排序(字典序)后，使用URL键值对的格式(即key1=value1&key2=value2…)拼接成字符串strA;
    2、生成sign（对strA进行md5运算得到strB，再拼接上key(即strB&key=?)得到strC，并对strC进行md5运算，得到sign）;
    3、strA拼接上sign和token(即strA&sign=?&token=?)，生成strD;
    4、生成最终字符串AUTHORIZATION（对strD进行base64编码，得到最终的字符串AUTHORIZATION）

#### 注释
    1、account：CwmG2XuSBGvTF62d
    2、key：a0e0b8d13bd1f92e723dd8b0c390b21b
    3、created：当前客户端时间戳(秒)
    4、nonce：由[a-zA-Z0-9]组成的8为随机字符串

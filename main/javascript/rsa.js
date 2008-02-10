/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/rsa.js
** | Begin :		10/07/2007
** | Last :			11/07/2007
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Chiffrage RSA en Javascript
** Necessite la librairie bigintegers.js
*/

/*
** Crypte une chaîne de caractère en utilisant l'algorithme RSA, en utilisant le modulus $mod et l'exposant $exp
*/
function encrypt_rsa(str, mod, exp)
{
	str = str.concat(String.fromCharCode(1));
	str = bin2int(str);

	var length = str.bitLength();
	var chunk_length = mod.bitLength() - 1;
	var block_length = Math.ceil(chunk_length / 8);
	var curr_pos = 0;
	var enc_data = '';
	while (curr_pos < length)
	{
		tmp = subint(str, curr_pos, chunk_length);
		enc_data = enc_data.concat(str_pad(int2bin(tmp.modPow(exp, mod)), block_length, String.fromCharCode(0)));
		curr_pos += chunk_length;
	}
	return (base64_encode(enc_data));
}

/*
** Crypte un champ du formulaire en RSA
*/
function encrypt_rsa_field(t, name, mod, exp)
{
	add_field(t, 'hidden', name + '_rsa', encrypt_rsa($(name + '_id').value, new BigInteger(mod), new BigInteger(exp)));
	$(name + '_id').value = '';
}

/*
** Converti un BigInteger en binaire
*/
function int2bin(num)
{
	var result = '';
	do
	{
		result = result.concat(String.fromCharCode(num.mod(new BigInteger('256'))));
		num = num.divide(new BigInteger('256'));
	}
	while (num != '0');
	return (result);
}

/*
** Converti du binaire en un BigInteger
*/
function bin2int(str)
{
	var result = new BigInteger('0');
	var n = str.length;

	do
	{
		o = str.substr(--n, 1).charCodeAt(0);
		tmp = result.multiply(new BigInteger('256'));
		tmp = tmp.add(new BigInteger(o.toString()));
		result = tmp;
	}
	while (n > 0);
	return (result);
}

/*
** Découpe une partie d'un BigInteger
*/
function subint(num, start, length)
{
	var start_byte = parseInt(start / 8);
	var start_bit = start % 8;
	var byte_length = parseInt(length / 8);
	var bit_length = length % 8;
	if (bit_length)
	{
		byte_length++;
	}

	num = num.divide(new BigInteger((1 << start_bit).toString()));
	var tmp = int2bin(num).substr(start_byte, byte_length);
	tmp = str_pad(tmp, byte_length, String.fromCharCode(0));
	var chr = String.fromCharCode(0xff >> (8 - bit_length));
	var bit = tmp.substr(byte_length - 1, 1);
	tmp = my_substr_replace(tmp, String.fromCharCode(bin2int(bit) & bin2int(chr)), byte_length - 1, byte_length);
	return (bin2int(tmp));
}

/*
** Remplace une portion de la chaîne $str depuis l'offset $start jusque $start + $length par $replace
*/
function my_substr_replace(str, replace, start, length)
{
	var new_str = '';
	if (!length)
	{
		length = start - str.length;
	}

	new_str = str.substr(0, start) + replace + str.substr(start + length);
	return (new_str);
}

/*
** Complète la chaîne $str jusqu'à la longueur $size avec le caractère $c
*/
function str_pad(str, size, c)
{
	while (str.length < size)
	{
		str = str.concat(c);
	}
	return (str);
}

/*
** Même effet que la fonction base64_encode() en PHP
** Tiré de la source http://www.javascriptfr.com/code.aspx?ID=15876
*/
function base64_encode(n)
{
	var dtable = new Array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/'); 
	var o1 = o2 = o3 = o4 = 0;
	var text = "";
	for (var i = 0; i < n.length; i += 3)
	{
		t = Math.min(3, n.length - i);
		if (t == 1)
		{
			x = n.charCodeAt(i);
			text += dtable[(x >> 2)];
			text += dtable[((x & 0X00000003) << 4)];
			text += '=';
			text += '=';
		}
		else if (t == 2)
		{
			x = n.charCodeAt(i);
			y = n.charCodeAt(i+1);

			text += dtable[(x >> 2)];
			text += dtable[((x & 0X00000003) << 4) | (y >> 4)];
			text += dtable[((y & 0X0000000f) << 2)];
			text += '=';
		}
		else
		{
			x = n.charCodeAt(i);
			y = n.charCodeAt(i+1);
			z = n.charCodeAt(i+2);
			text += dtable[x >> 2];
			text += dtable[((x & 0x00000003) << 4) | (y >> 4)];
			text += dtable[((y & 0X0000000f) << 2) | (z >> 6)];
			text += dtable[z & 0X0000003f];
		}
	}
	return text;
}
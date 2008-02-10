/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/admin.js
** | Begin :		19/12/2005
** | Last :			09/07/2007
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

var adm_menu_pos = new Array();
var adm_menu_height = new Array();

/*
** Affiche / Cache un div au niveau du menu administratif
** -----
** id ::		ID du div
*/
function hide_menu(id)
{
	adm_menu_pos[id] ^= true;

	// On sauve la position du menu dans un cookie
	if (adm_menu_pos[id])
	{
		adm_menu_height[id] = $(id).offsetHeight;
		$(id).effects({duration: 500}).custom(
			{
				'height': [adm_menu_height[id], 0],
				'opacity': [1, 0]
			}
		);
		SetCookie(id, "C", true);
	}
	else
	{
		$(id).effects({duration: 500}).custom(
			{
				'height': [0, adm_menu_height[id]],
				'opacity': [0, 1]
			}
		);
		SetCookie(id, "O", true);
	}
}

/*
** Fonction appelée lors du chargement de l'administration
*/
function init_admin()
{
	if (typeof len != 'undefined')
	{
		for (var i = 0; i < len; i++)
		{
			if (ReadCookie(block_menu[i]) == "C")
			{
				adm_menu_height['menu_' + i] = $(block_menu[i]).offsetHeight;
				$(block_menu[i]).style.height = '0px';
				$(block_menu[i]).style.opacity = '0';
				adm_menu_pos['menu_' + i] = true;
			}
		}
	}
}
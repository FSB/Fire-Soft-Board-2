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
var adm_menu_fx = {};

/*
** Affiche / Cache un div au niveau du menu administratif
** -----
** id ::		ID du div
*/
function hide_menu(id)
{
	adm_menu_pos[id] ^= true;
	
	if ($defined(adm_menu_fx[id]))
	{
		adm_menu_fx[id].stop();
	}
	else
	{
		adm_menu_fx[id] = new Fx.Styles(id,
		{
			duration: 500,
			transition: Fx.Transitions.linear
		});
	}

	// On sauve la position du menu dans un cookie
	if (adm_menu_pos[id])
	{
		if (!$defined(adm_menu_height[id]))
		{
			adm_menu_height[id] = $(id).getCoordinates().height;
		}

		adm_menu_fx[id].start({
			height: [$(id).getStyle('height'), 0],
			opacity: [$(id).getStyle('opacity'), 0]
		});

		Cookie.set(id, "C", {duration: 31});
	}
	else
	{
		$(id).setStyle('display', 'block');
		adm_menu_fx[id].start({
			height: [$(id).getStyle('height'), adm_menu_height[id]],
			opacity: [$(id).getStyle('opacity'), 1]
		});

		Cookie.set(id, "O", true);
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
			if (Cookie.get(block_menu[i]) == "C")
			{
				adm_menu_height['menu_' + i] = $(block_menu[i]).getCoordinates().height;
				$(block_menu[i]).setStyle('display', 'none');
				$(block_menu[i]).setStyle('height', '0px');
				$(block_menu[i]).setOpacity('0');
				adm_menu_pos['menu_' + i] = true;
			}
		}
	}
}
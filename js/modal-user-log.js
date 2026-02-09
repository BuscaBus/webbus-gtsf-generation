/*Script para abrir o modal cadastrar usuário na tela de login*/
const btn_cad = document.querySelector("#btn-cad")
const modal_cad = document.querySelector("dialog")

btn_cad.onclick = function ()
{
    modal_cad.showModal()    
}

/*Script para fechar o modal da tela de login*/
const buttonClose = document.querySelector("dialog button")

buttonClose.onclick = function ()
{
    modal_cad.close()       
}


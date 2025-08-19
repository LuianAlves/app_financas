import { generateCRUD } from "../common/swipeCrud.js";

const brandMap = {
    1: 'Visa', 2: 'Mastercard', 3: 'American Express',
    4: 'Discover', 5: 'Diners Club', 6: 'JCB', 7: 'Elo'
};
const assetUrl = "assets/img";

generateCRUD({
    route: route,
    inputId: "card_id",
    modalId: "#modalCard",
    formId: "#formCard",
    listSelector: "#cardList",
    renderCard: card => `
    <div class="balance-box" style="background: ${card.color_card}">
      <img src="${assetUrl}/credit_card/chip_card.png" class="card-chip"/>
      <img src="${assetUrl}/brands/${brandMap[card.brand]}.png" class="card-brand"/>
      <div class="card-number">**** **** **** ${card.last_four_digits}</div>
      <div class="card-details">
        <div class="detail-row mb-3">
          <div class="detail-left">${card.cardholder_name}</div>
          <div class="detail-right">${card.account.bank_name}</div>
        </div>
        <div class="detail-row flex-column" style="font-size:10px;letter-spacing:1px;">
          <div>Fatura: ${parseFloat(card.current_invoice).toLocaleString('pt-BR',{style:'currency',currency:'BRL'})}</div>
          <div>Limite: ${parseFloat(card.credit_limit).toLocaleString('pt-BR',{style:'currency',currency:'BRL'})}</div>
        </div>
      </div>
      <button class="view-card-btn"  data-id="${card.id}">👁️</button>
      <button class="edit-card-btn"  data-id="${card.id}">✏️</button>
      <button class="delete-card-btn" data-id="${card.id}">🗑️</button>
    </div>
  `,
    fillModal: data => {
        document.querySelector("input[name='cardholder_name']").value = data.cardholder_name;
        document.querySelector("input[name='last_four_digits']").value = data.last_four_digits;
        document.querySelector("select[name='brand']").value = data.brand;
        document.querySelector("input[name='credit_limit']").value = data.credit_limit;
        document.querySelector("input[name='closing_day']").value = data.closing_day;
        document.querySelector("input[name='due_day']").value = data.due_day;
    }
});

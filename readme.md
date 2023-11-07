## COMPONENT CAKEPHP PARA API FACEBOOK MARKETING ##

Programadores PHP que utilizam o framework CAKEPHP 
Podem utilizar essa estrutura para a criação de um component que irá facilitar a conexão com a API do facebook marketing

## METODOS BÁSICO DISPONÍVEIS ##

```getURLAuth()```

Resposável por gerar a URL de autenticação e liberação de permissões


```getToken()```

Resposável obter o token de acesso para todas as futuras transações. O Token sempre deve ser enviado


```getAllAccountFBPage()```

Listar todas as páginas do facebook que o usuário autenticado tem acesso.



```getInsights()```

Ler insights sobre as campanhas de tráfego pago


``` getTargetInteress() ```

Listar os principais interesses disponíveis para campanhas, esses interesses não são exibidos na pesquisa padrão disponível na interface do usuário no gerenciador de anúncios.

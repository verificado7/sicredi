FROM php:8.1-cli

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia todos os arquivos para o container
COPY . .

# Expõe a porta que o PHP vai usar
EXPOSE 10000

# Comando para iniciar o servidor PHP embutido
CMD ["php", "-S", "0.0.0.0:10000"]

<?php

namespace Coyote\Http\Forms\User;

use Coyote\Services\FormBuilder\Form;
use Coyote\Services\FormBuilder\FormEvents;
use Coyote\Services\Geocoder\GeocoderInterface;
use Coyote\User;

class SettingsForm extends Form
{
    /**
     * @var GeocoderInterface
     */
    protected $geocoder;

    /**
     * @param GeocoderInterface $geocoder
     */
    public function __construct(GeocoderInterface $geocoder)
    {
        parent::__construct();

        $this->geocoder = $geocoder;
        $this->bindFormEvents();
    }

    public function buildForm()
    {
        /** @var \Coyote\User $user */
        $user = $this->getData();

        // id uzytkownika, ktorego ustawienia wlasnie edytujemy
        $userId = $user->id;
        $groupList = $user->groups()->pluck('name', 'id')->toArray();

        $this->add('email', 'email', [
            'rules' => 'required|email|email_unique:' . $userId,
            'label' => 'E-mail',
            'help' => 'Jeżeli chcesz zmienić adres e-mail, na nową skrzynkę zostanie wygenerowany klucz aktywacyjny.',
            'row_attr' => [
                'id' => 'email'
            ]
        ]);

        if ($groupList) {
            $this->add('group_id', 'select', [
                'rules' => 'nullable|integer|exists:group_users,group_id,user_id,' . $userId,
                'label' => 'Domyślna grupa',
                'help' => 'Nazwa grupy będzie wyświetlana pod avatarem, np. na forum.',
                'choices' => $groupList,
                'empty_value' => '-- wybierz --'
            ]);
        }

        $this
            ->add('date_format', 'select', [
                'label' => 'Format daty',
                'choices' => User::dateFormatList()
            ])
            ->add('website', 'text', [
                'rules'  => 'nullable|url|reputation:50',
                'label' => 'Strona WWW',
                'help' => 'Strona domowa, blog, portfolio itp.'
            ])
            ->add('allow_smilies', 'checkbox', [
                'rules' => 'boolean',
                'label' => 'Pokazuj emotikony'
            ])
            ->add('allow_subscribe', 'checkbox', [
                'rules' => 'boolean',
                'label' => 'Automatycznie obserwuj wątki oraz wpisy na mikroblogu, w których biorę udział'
            ])
            ->add('allow_sticky_header', 'checkbox', [
                'rules' => 'boolean',
                'label' => 'Przyklejony pasek menu',
                'help' => 'Pasek menu będzie zawsze widoczny podczas przewijania okna.'
            ])
            ->add('firm', 'text', [
                'rules' => 'nullable|string|max:100',
                'label' => 'Nazwa firmy',
            ])
            ->add('position', 'text', [
                'rules' => 'nullable|string|max:100',
                'label' => 'Stanowisko',
                'attr' => [
                    'placeholder' => 'Np. Junior Java Developer'
                ]
            ])
            ->add('github', 'text', [
                'rules' => 'nullable|string|max:200',
                'label' => 'Konto Github',
                'help' => 'Nazwa użytkownika lub link do konta Github.'
            ])
            ->add('bio', 'textarea', [
                'rules' => 'nullable|string|max:500',
                'label' => 'O sobie',
                'help' => 'W tym polu możesz zamieścić krótką informację o sobie, czym się zajmujesz, co cię interesuje. Ta informacja zostanie wyświetlona na Twoim profilu.',
                'attr' => [
                    'rows' => 3
                ]
            ])
            ->add('birthyear', 'select', [
                'rules' => 'nullable|integer|between:1950,' . (date('Y') - 1),
                'label' => 'Rok urodzenia',
                'help' => 'Na podstawie roku urodzenia, w Twoim profilu będzie widoczny Twój wiek.',
                'choices' => User::birthYearList(),
                'empty_value' => '--'
            ])
            ->add('location', 'text', [
                'rules' => 'nullable|string|max:50',
                'label' => 'Miejsce zamieszkania',
                'attr' => [
                    'placeholder' => 'Nazwa miejscowości'
                ]
            ])
            ->add('allow_count', 'checkbox', [
                'rules' => 'boolean',
                'label' => 'Pokazuj licznik postów'
            ])
            ->add('allow_sig', 'checkbox', [
                'rules' => 'boolean',
                'label' => 'Pokazuj sygnaturki użytkowników'
            ])
            ->add('sig', 'textarea', [
                'rules' => 'nullable|string|max:499|spam_link:50',
                'label' => 'Sygnatura',
                'help' => 'Podpis będzie widoczny przy każdym Twoim poście. Uwaga! Użytkownicy posiadający mniej niż 50 punktów reputacji nie mogą umieszczać linków w tym polu.',
                'attr' => [
                    'rows' => 3
                ]
            ])
            ->add('submit', 'submit', [
                'label' => 'Zapisz',
                'attr' => [
                    'data-submit-state' => 'Wysyłanie...'
                ]
            ]);
    }

    protected function bindFormEvents()
    {
        $this->addEventListener(FormEvents::POST_SUBMIT, function (Form $form) {
            $form->add('latitude', 'hidden', ['value' => null]);
            $form->add('longitude', 'hidden', ['value' => null]);

            if ($form->get('location')->getValue()) {
                $location = $this->geocoder->geocode($form->get('location')->getValue());

                $form->get('latitude')->setValue($location->latitude);
                $form->get('longitude')->setValue($location->longitude);
            }
        });

        $this->addEventListener(FormEvents::POST_SUBMIT, function (Form $form) {
            $github = $form->get('github')->getValue();

            if ($github) {
                if (filter_var($github, FILTER_VALIDATE_URL) === false) {
                    $form->get('github')->setValue('https://github.com/' . $github);
                }
            }
        });
    }
}

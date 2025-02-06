import { JSX, useState } from "react";

class ResultData {
    error?: Error;
    received?: string;
    brandData?: BrandData;
    parsedData?: string;

    constructor(resData: any) {
        resData.error && (this.error = resData.error);
        resData.received && (this.received = resData.received);
        resData.brandData &&
            (this.brandData = new BrandData(resData.brandData));
        resData.parsedData && (this.parsedData = resData.parsedData);
    }
}

class BrandData {
    colors?: ColorData;
    fonts?: FontData;

    constructor(brandData: Record<string, any>) {
        brandData.colors && (this.colors = brandData.colors as ColorData);
        brandData.fonts && (this.fonts = brandData.fonts as FontData);
    }
}

interface ColorData {
    [color: string]: string[];
}

interface FontData {
    [font: string]: string[];
}

const Home = (): JSX.Element => {
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [resData, setResData] = useState(null);

    const handleSubmit = () => {
        // const fetchAddress = "/api/brand_scraper";
        const fetchAddress = "api/test";

        setLoading(true);
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(new ResultData(data) as React.SetStateAction<null>);
                setLoading(false);
            });
    };

    return (
        <div id='main' className="relative h-screen grid grid-rows-[auto_1fr_auto]" >
            <div id='header' className="p-2">
                <h1>Brand Scraper</h1>
            </div>
            {(!loading && !resData) ?
                <div id='content-container' className="max-w-md mt-[30vh]">
                    <h2 id="intro" className='text-center'>Scrape a website for its brand colors and fonts.</h2>
                </div> :
                <div id='content-container' className="pt-10 max-wd-lg">
                    {loading && <Loading />}
                    {resData ? <ResultsDisplay resData={resData} /> : null}
                </div>
            }
            <section id='input-container' className="w-full ">
                <div id='input ' className="mx-auto w-fit p-4">
                    <input
                        className="rounded-tl-sm rounded-bl-sm max-w-sm bg-inputcolor w-screen px-4 py-2 text-lg focus:outline-none"
                        type="text"
                        placeholder="Enter a website URL"
                        onChange={(e) => setInput(e.target.value)}
                    ></input>
                    <button className='rounded-tr-sm rounded-br-sm bg-inputbtncolor px-4 py-2 text-lg text-gray-200 hover:text-white' onClick={handleSubmit}>Submit</button>
                </div>
            </section>
        </div>
    );
};

const Loading = (): JSX.Element => {
    return <div className="loadingResults">Loading</div>;
};

const ResultsDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                <ErrorDisplay error={resData.error} />
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }: { error: Error }): JSX.Element => {
    return <div className="errorDisplay"><p>{error.toString()}</p></div>;
};

const DataDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div className="dataDisplay">
            <h3>Brand Colors for {resData.received}</h3>
            {resData.brandData!.colors ? (
                <ColorDisplay colors={resData.brandData!.colors} />
            ) : null}
            {resData.brandData!.fonts ? (
                <FontDisplay fonts={resData.brandData!.fonts} />
            ) : null}
            {resData.parsedData && (<ParsedDataDisplay parsedData={resData.parsedData} />)}

        </div>
    );
};

const ColorDisplay = ({ colors }: { colors: ColorData }): JSX.Element => {
    return (
        <div className="colorDisplay">
            <h3>Colors</h3>
            <p>{JSON.stringify(colors, null, 2)}</p>
        </div>
    );
};

const FontDisplay = ({ fonts }: { fonts: FontData }): JSX.Element => {
    return (
        <div className="fontDisplay">
            <h3>Fonts</h3>
            <p>{JSON.stringify(fonts, null, 2)}</p>
        </div>
    );
};

const ParsedDataDisplay = ({
    parsedData,
}: {
    parsedData: string;
}): JSX.Element => {
    return (
        <div className="fullDataDisplay">
            <h3>All Parsed Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
